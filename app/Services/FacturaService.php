<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Usuario;
use App\Models\EstatusServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FacturaService
{
    public function __construct(
        private MorosidadService $morosidadService
    ) {}

    /**
     * Crear una factura completa con toda la lógica de negocio
     */
    public function crearFactura(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $datos = $this->extraerDatosBase($request);

            // 1. Validar prepay vigente
            if ($error = $this->validarPrepayVigente($datos)) {
                return $error;
            }

            // 2. Generar siguiente folio
            $nextFolio = $this->generarFolio();

            // 3. Procesar según tipo
            $resultado = match($datos['tipo']) {
                'baja_temporal' => $this->procesarBajaTemporal($datos),
                'cancelacion' => $this->procesarCancelacion($datos),
                default => $this->procesarPagoNormal($datos),
            };

            if ($resultado['error'] ?? false) {
                return $resultado['response'];
            }

            $total = $resultado['total'];
            $payload = $resultado['payload'];

            // 4. Aplicar edición manual si existe
            $manualOverride = $this->procesarEdicionManual($datos, $total, $payload);

            // 5. Validar duplicados
            if ($duplicado = $this->buscarDuplicado($datos, $payload, $total)) {
                return $this->retornarDuplicado($duplicado, $datos);
            }

            // 6. Crear factura
            $factura = $this->guardarFactura($nextFolio, $datos, $total, $payload, $manualOverride);

            // 7. Actualizar usuario según tipo
            $this->actualizarEstatusUsuario($factura, $datos['tipo']);

            // 8. Registrar auditoría
            $this->registrarAuditoria($factura, $datos, $manualOverride);

            return [
                'ok' => true,
                'referencia' => $factura->reference_number,
                'id' => $factura->id,
            ];
        });
    }

    /**
     * Extraer datos base del request
     */
    private function extraerDatosBase(Request $request): array
    {
        $payload = $request->input('payload', []);
        $esAdeudoManual = !empty($payload['es_adeudo_manual']);

        $mesSiguiente = !empty($payload['mes_siguiente']);
        $periodoOverride = isset($payload['periodo_override']) && preg_match('/^\d{4}-\d{2}$/', $payload['periodo_override'])
            ? $payload['periodo_override']
            : null;

        // Si es adeudo manual, intentamos parsear de la descripción o del periodo_override
        $periodo = $periodoOverride;
        if ($esAdeudoManual) {
            $desc = $payload['label'] ?? '';
            $parsed = $this->morosidadService->parsePeriodoFromDescripcion($desc);
            if ($parsed) {
                $periodo = $parsed;
            }
            // Si no se pudo parsear y no hay override, NO asignamos mes actual para adeudos manuales
        }

        // Si aún no hay periodo y no es adeudo manual, usamos el default (mes actual o siguiente)
        if (!$periodo && !$esAdeudoManual) {
            $periodo = $mesSiguiente ? now()->addMonth()->format('Y-m') : now()->format('Y-m');
        }

        return [
            'periodo' => $periodo,
            'numero' => $request->input('numero_servicio'),
            'usuarioId' => $request->input('usuario_id'),
            'payload' => $payload,
            'request' => $request,
            'tipo' => match(true) {
                ($payload['otro'] ?? null) === 'baja_temporal' => 'baja_temporal',
                ($payload['otro'] ?? null) === 'cancelacion' => 'cancelacion',
                ($payload['prepay'] ?? null) === 'si' => 'prepay',
                default => 'normal',
            },
        ];
    }

    /**
     * Validar si existe un prepay vigente
     */
    private function validarPrepayVigente(array $datos): ?array
    {
        if (! $datos['numero'] || in_array($datos['tipo'], ['baja_temporal', 'cancelacion'])) {
            return null;
        }

        $prepay = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $datos['numero'])
            ->where(fn($q) => $q->where('payload->prepay', 'si')->orWhere('payload->prepay', true))
            ->orderByDesc('id')
            ->first(['id', 'payload', 'created_at']);

        if (! $prepay) {
            return null;
        }

        $p = is_array($prepay->payload)
            ? $prepay->payload
            : (is_string($prepay->payload) ? @json_decode($prepay->payload, true) : []);

        $months = (int) (($p['prepay_months'] ?? 0) ?: 0);
        $venceAt = PrepayDashboardService::venceAt(
            $prepay->created_at ? Carbon::parse($prepay->created_at) : null,
            $months
        );
        $estado = PrepayDashboardService::estadoPorVencimiento($venceAt, now());

        if ($venceAt && ! $estado['vencido']) {
            return [
                'ok' => false,
                'message' => 'Pago adelantado vigente hasta ' . $venceAt->locale('es')->translatedFormat('F Y'),
                'code' => 409,
            ];
        }

        return null;
    }

    /**
     * Generar siguiente número de folio
     */
    private function generarFolio(): int
    {
        $row = DB::table('invoice_sequences')
            ->where('name', 'facturas')
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('invoice_sequences')->insert([
                'name' => 'facturas',
                'current_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $current = 0;
        } else {
            $current = (int) $row->current_value;
        }

        $next = $current + 1;

        DB::table('invoice_sequences')
            ->where('name', 'facturas')
            ->update(['current_value' => $next, 'updated_at' => now()]);

        return $next;
    }

    /**
     * Procesar baja temporal
     */
    private function procesarBajaTemporal(array $datos): array
    {
        if (! $datos['numero'] || ! ctype_digit((string) $datos['numero'])) {
            return ['error' => true, 'response' => ['ok' => false, 'message' => 'Número inválido', 'code' => 422]];
        }

        $adeudo = $this->morosidadService->calcularAdeudoUsuario((string) $datos['numero'], null);
        if (! ($adeudo['ok'] ?? false)) {
            return ['error' => true, 'response' => ['ok' => false, 'message' => $adeudo['message'] ?? 'No se pudo validar adeudos', 'code' => 409]];
        }

        $adeudoPendiente = round((float) ($adeudo['pendiente'] ?? 0), 2);
        $months = (int) ($datos['payload']['baja_temporal_months'] ?? 0);

        if ($months < 1 || $months > 6) {
            return ['error' => true, 'response' => ['ok' => false, 'message' => 'Meses de baja temporal inválidos (1–6)', 'code' => 422]];
        }

        $mensualidad = $this->obtenerMensualidad($datos['usuarioId'], $datos['numero'], $datos['payload']);
        if ($mensualidad <= 0) {
            return ['error' => true, 'response' => ['ok' => false, 'message' => 'No se pudo determinar la mensualidad', 'code' => 422]];
        }

        $bajaTotal = round($mensualidad * 0.2 * $months, 2);
        $descuento = round((float) ($datos['payload']['descuento'] ?? 0), 2);
        $total = round(max(0, $adeudoPendiente + $bajaTotal - $descuento), 2);

        $payload = array_merge($datos['payload'], [
            'mensualidad' => $mensualidad,
            'otro' => 'baja_temporal',
            'recargo' => 'no',
            'prepay' => 'no',
            'prepay_months' => null,
            'prepay_total' => null,
            'adeudo_pendiente' => $adeudoPendiente,
            'baja_temporal_months' => $months,
            'baja_temporal_total' => $bajaTotal,
            'adeudo_prev' => $adeudoPendiente,
            'adeudo_nuevo' => round($adeudoPendiente + $bajaTotal, 2),
        ]);

        return ['error' => false, 'total' => $total, 'payload' => $payload];
    }

    /**
     * Procesar cancelación de servicio
     */
    private function procesarCancelacion(array $datos): array
    {
        $payload = array_merge($datos['payload'], [
            'otro' => 'cancelacion',
            'recargo' => 'no',
            'prepay' => 'no',
            'prepay_months' => null,
            'prepay_total' => null,
        ]);

        $total = ($payload['manual_total_enabled'] ?? false)
            ? round((float) ($datos['payload']['manual_total_value'] ?? 0), 2)
            : 0.0;

        return ['error' => false, 'total' => $total, 'payload' => $payload];
    }

    /**
     * Procesar pago normal
     */
    private function procesarPagoNormal(array $datos): array
    {
        return [
            'error' => false,
            'total' => round((float) ($datos['request']->input('total', 0)), 2),
            'payload' => $datos['payload'],
        ];
    }

    /**
     * Obtener mensualidad del usuario
     */
    private function obtenerMensualidad(?int $usuarioId, ?string $numero, array $payload): float
    {
        $u = ! empty($usuarioId)
            ? Usuario::find($usuarioId)
            : Usuario::where('numero_servicio', (string) $numero)->first();

        if ($u && $u->tarifa !== null) {
            return (float) $u->tarifa;
        }

        return (float) preg_replace('/[^\d.]/', '', (string) ($payload['mensualidad'] ?? 0));
    }

    /**
     * Procesar edición manual del total
     */
    private function procesarEdicionManual(array $datos, float &$total, array &$payload): ?array
    {
        if (! ($datos['payload']['manual_total_enabled'] ?? false)) {
            return null;
        }

        $reason = trim((string) ($datos['payload']['manual_total_reason'] ?? ''));
        if ($reason === '') {
            throw new \RuntimeException('Motivo requerido para editar el total');
        }

        $manualValue = $datos['payload']['manual_total_value'] ?? null;
        if (! is_numeric($manualValue) || (float) $manualValue < 0) {
            throw new \RuntimeException('Total manual inválido');
        }

        $manualValue = round((float) $manualValue, 2);
        $prevTotal = $total;
        $total = $manualValue;

        $payload['manual_total_enabled'] = true;
        $payload['manual_total_value'] = $manualValue;
        $payload['manual_total_reason'] = mb_substr($reason, 0, 250);

        return [
            'prev_total' => $prevTotal,
            'new_total' => $manualValue,
            'reason' => mb_substr($reason, 0, 250),
        ];
    }

    /**
     * Buscar factura duplicada
     */
    private function buscarDuplicado(array $datos, array $payload, float $total): ?Factura
    {
        if (in_array($datos['tipo'], ['baja_temporal', 'cancelacion'])) {
            return null;
        }

        $periodoFactura = $datos['periodo'];
        $payloadJson = json_encode($payload);

        $fingerprintData = [
            'numero_servicio' => $datos['numero'],
            'periodo' => $periodoFactura,
            'total' => $total,
            'nombre' => $payload['nombre'] ?? null,
            'mensualidad' => $payload['mensualidad'] ?? null,
            'recargo' => $payload['recargo'] ?? null,
            'pago_anterior' => $payload['pago_anterior'] ?? null,
            'metodo' => $payload['metodo'] ?? 'Efectivo',
            'manual_label' => !empty($payload['es_adeudo_manual']) ? ($payload['label'] ?? null) : null,
        ];

        $fingerprint = hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Buscar por fingerprint
        $existing = Factura::where('fingerprint', $fingerprint)
            ->orWhere(function ($q) use ($datos, $total, $payloadJson, $periodoFactura) {
                $q->where('numero_servicio', $datos['numero'])
                    ->where('periodo', $periodoFactura)
                    ->where('total', $total)
                    ->whereRaw('payload = ?', [$payloadJson]);
            })
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->fingerprint_match = true;
            return $existing;
        }

        // Buscar facturas canceladas (trashed) por fingerprint
        $trashed = Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
        if ($trashed) {
            $trashed->is_trashed = true;
            return $trashed;
        }

        // Los pagos adelantados pueden coexistir con el pago mensual del mismo periodo
        if ($datos['tipo'] === 'prepay') {
            return null;
        }

        // Validar duplicado por periodo (solo si el periodo no es null)
        if ($periodoFactura !== null) {
            $dup = Factura::where('periodo', $periodoFactura)
                ->where(function ($q) use ($datos) {
                    if ($datos['numero']) {
                        $q->where('numero_servicio', $datos['numero']);
                    }
                    if (! empty($datos['usuarioId'])) {
                        $q->orWhere('usuario_id', $datos['usuarioId']);
                    }
                })
                ->first();

            if ($dup) {
                $dup->is_duplicate_period = true;
                return $dup;
            }
        }

        return null;
    }

    /**
     * Retornar respuesta de duplicado
     */
    private function retornarDuplicado(Factura $duplicado, array $datos): array
    {
        $periodoFactura = in_array($datos['tipo'], ['baja_temporal', 'cancelacion']) ? null : $datos['periodo'];

        // Es duplicado por periodo (error)
        if ($duplicado->is_duplicate_period ?? false) {
            DB::table('payment_attempts')->insert([
                'usuario_id' => $datos['usuarioId'],
                'numero_servicio' => $datos['numero'],
                'periodo' => $periodoFactura,
                'status' => 'duplicate',
                'reason' => 'Pago ya registrado para este periodo',
                'created_by' => optional($datos['request']->user())->id,
                'payload' => json_encode($datos['payload']),
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'ok' => false,
                'message' => 'Ya existe un pago registrado para este periodo',
                'referencia' => $duplicado->reference_number,
                'periodo' => $periodoFactura,
                'code' => 409,
            ];
        }

        // Es reimpresión (éxito)
        $reason = ($duplicado->is_trashed ?? false)
            ? 'Reimpresión / reuso de factura (trashed)'
            : 'Reimpresión / reuso de factura';

        DB::table('payment_attempts')->insert([
            'usuario_id' => $datos['usuarioId'],
            'numero_servicio' => $datos['numero'],
            'periodo' => $periodoFactura,
            'status' => 'success',
            'reason' => $reason,
            'created_by' => optional($datos['request']->user())->id,
            'payload' => json_encode($datos['payload']),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'ok' => true,
            'referencia' => $duplicado->reference_number,
            'id' => $duplicado->id,
            'reused' => true,
        ];
    }

    /**
     * Guardar factura en base de datos
     */
    private function guardarFactura(int $folio, array $datos, float $total, array $payload, ?array $manualOverride): Factura
    {
        $periodoFactura = in_array($datos['tipo'], ['baja_temporal', 'cancelacion']) ? null : $datos['periodo'];

        $fingerprintData = [
            'numero_servicio' => $datos['numero'],
            'periodo' => $periodoFactura,
            'total' => $total,
            'nombre' => $payload['nombre'] ?? null,
            'mensualidad' => $payload['mensualidad'] ?? null,
            'recargo' => $payload['recargo'] ?? null,
            'pago_anterior' => $payload['pago_anterior'] ?? null,
            'metodo' => $payload['metodo'] ?? 'Efectivo',
            'manual_label' => !empty($payload['es_adeudo_manual']) ? ($payload['label'] ?? null) : null,
        ];

        $fingerprint = in_array($datos['tipo'], ['baja_temporal', 'cancelacion'])
            ? null
            : hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        try {
            $factura = new Factura;
            $factura->reference_number = $folio;
            $factura->usuario_id = $datos['usuarioId'];
            $factura->numero_servicio = $datos['numero'];
            $factura->periodo = $periodoFactura;
            $factura->total = $total;
            $factura->payload = $payload;
            $factura->created_by = optional($datos['request']->user())->id;
            $factura->fingerprint = $fingerprint;
            $factura->save();

            return $factura;
        } catch (\Illuminate\Database\QueryException $e) {
            // Carrera - buscar la factura que ya se creó
            $c = $fingerprint ? Factura::withTrashed()->where('fingerprint', $fingerprint)->first() : null;
            if ($c) {
                return $c;
            }
            throw $e;
        }
    }

    /**
     * Actualizar estatus del usuario según tipo de factura
     */
    private function actualizarEstatusUsuario(Factura $factura, string $tipo): void
    {
        switch ($tipo) {
            case 'cancelacion':
                $this->cancelarServicio($factura);
                break;
            case 'baja_temporal':
                $this->aplicarBajaTemporal($factura);
                break;
            default:
                $this->marcarComoPagado($factura);
                break;
        }
    }

    /**
     * Cancelar servicio del usuario
     */
    private function cancelarServicio(Factura $factura): void
    {
        $usuario = $factura->usuario_id
            ? Usuario::find($factura->usuario_id)
            : Usuario::where('numero_servicio', $factura->numero_servicio)->first();

        if (! $usuario) return;

        $prev = [
            'estatus_servicio_id' => $usuario->estatus_servicio_id,
            'estado_id' => $usuario->estado_id,
        ];

        $usuario->update([
            'estatus_servicio_id' => 3, // Cancelado
            'estado_id' => 2,
        ]);

        $this->logAuditoria('usuario_cancelacion_servicio', 'usuarios', $usuario->id, $prev, [
            'estatus_servicio_id' => 3,
            'estado_id' => 2,
        ]);
    }

    /**
     * Aplicar baja temporal al usuario
     */
    private function aplicarBajaTemporal(Factura $factura): void
    {
        $baja = EstatusServicio::whereRaw('LOWER(nombre) = ?', ['baja temporal'])->first();
        if (! $baja) {
            $baja = EstatusServicio::create(['nombre' => 'Baja temporal']);
        }

        $usuario = $factura->usuario_id
            ? Usuario::find($factura->usuario_id)
            : Usuario::where('numero_servicio', $factura->numero_servicio)->first();

        if (! $usuario) return;

        $prev = [
            'estatus_servicio_id' => $usuario->estatus_servicio_id,
            'adeudo_monto' => $usuario->adeudo_monto,
            'adeudo_descripcion' => $usuario->adeudo_descripcion,
        ];

        $usuario->update([
            'estatus_servicio_id' => $baja->id,
            'adeudo_monto' => 0,
            'adeudo_descripcion' => null,
        ]);

        $this->logAuditoria('usuario_baja_temporal', 'usuarios', $usuario->id, $prev, [
            'estatus_servicio_id' => $baja->id,
            'adeudo_monto' => 0,
            'adeudo_descripcion' => null,
        ]);
    }

    /**
     * Marcar usuario como pagado
     */
    private function marcarComoPagado(Factura $factura): void
    {
        $usuario = $factura->usuario_id
            ? Usuario::find($factura->usuario_id)
            : Usuario::where('numero_servicio', $factura->numero_servicio)->first();

        if (! $usuario) return;

        $adeudoMonto = (float) ($usuario->adeudo_monto ?? 0);
        $mensualidad = (float) preg_replace('/[^\d.]/', '', (string) ($usuario->tarifa ?? 0));
        $totalPagado = (float) ($factura->total ?? 0);
        
        $payload = is_array($factura->payload) ? $factura->payload : (is_string($factura->payload) ? @json_decode($factura->payload, true) : []);
        $esAdeudoManual = !empty($payload['es_adeudo_manual']);

        // Primero, actualizar el adeudo manual si corresponde
        // Lo limpiamos si es un pago marcado como manual O si el pago es suficiente para cubrir la deuda de Excel
        $limpiarAdeudoManual = $esAdeudoManual || ($adeudoMonto > 0 && $totalPagado >= ($mensualidad - 0.01));
        
        if ($limpiarAdeudoManual && $adeudoMonto > 0) {
            $usuario->adeudo_monto = 0;
            $usuario->adeudo_descripcion = null;
            $usuario->save();
        }

        // Ahora calcular el adeudo real para decidir el estatus
        // IMPORTANTE: calcularAdeudoUsuario usa el periodo actual por defecto
        $adeudoReal = $this->morosidadService->calcularAdeudoUsuario($usuario->numero_servicio, null);
        $tienePendiente = (float) ($adeudoReal['pendiente'] ?? 0) > 0.01;

        $updateData = [
            'estatus_servicio_id' => $tienePendiente ? 4 : 1, // 4: Pendiente, 1: Pagado
            'estado_id' => 1,
        ];

        $usuario->update($updateData);
    }

    /**
     * Registrar auditoría del pago
     */
    private function registrarAuditoria(Factura $factura, array $datos, ?array $manualOverride): void
    {
        $periodoFactura = in_array($datos['tipo'], ['baja_temporal', 'cancelacion']) ? null : $datos['periodo'];

        if ($manualOverride) {
            DB::table('audit_logs')->insert([
                'actor_user_id' => optional($datos['request']->user())->id,
                'actor_role' => optional($datos['request']->user())->role,
                'actor_name' => optional($datos['request']->user())->name,
                'action' => 'factura_total_override',
                'table_name' => 'facturas',
                'entity_type' => Factura::class,
                'entity_id' => (string) $factura->id,
                'prev_values' => json_encode(['total' => $manualOverride['prev_total']]),
                'new_values' => json_encode(['total' => $manualOverride['new_total'], 'reason' => $manualOverride['reason']]),
                'ip' => $datos['request']->ip(),
                'user_agent' => (string) $datos['request']->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Log del pago exitoso
        DB::table('payment_attempts')->insert([
            'usuario_id' => $datos['usuarioId'],
            'numero_servicio' => $datos['numero'],
            'periodo' => $periodoFactura,
            'status' => 'success',
            'reason' => 'Factura creada',
            'created_by' => optional($datos['request']->user())->id,
            'payload' => json_encode($datos['payload']),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log de auditoría genérico
     */
    private function logAuditoria(string $action, string $table, int $entityId, array $prev, array $new): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => auth()->id(),
            'actor_role' => auth()->user()?->role,
            'actor_name' => auth()->user()?->name,
            'action' => $action,
            'table_name' => $table,
            'entity_type' => Usuario::class,
            'entity_id' => (string) $entityId,
            'prev_values' => json_encode($prev),
            'new_values' => json_encode($new),
            'ip' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
