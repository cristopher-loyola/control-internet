<?php

namespace App\Http\Controllers\Chivato;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CorteCaja;
use App\Models\Factura;
use App\Models\Usuario;
use App\Services\MorosidadService;
use App\Services\PrepayDashboardService;
use App\Services\WhatsAppNotifierService;
use App\Services\ZonaDashboardService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChivatoController extends Controller
{
    public function index(Request $request)
    {
        $zona = 'Chivato';
        $zonaKey = 'chivato';
        $user = $request->user();

        // Buscar corte activo para mostrar botón de exportar
        $corteActivo = CorteCaja::obtenerActivo($zonaKey, $user->id);

        return view('chivato.index', [
            'zona' => $zona,
            'stats' => ZonaDashboardService::stats($zona),
            'chart' => ZonaDashboardService::chartNewClientsLast7Days($zona),
            'payments' => ZonaDashboardService::recentPayments($zona, 10),
            'corteActivo' => $corteActivo,
        ]);
    }

    public function pagos(Request $request)
    {
        return view('chivato.pagos');
    }

    public function corte(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Buscar corte activo
        $corteActivo = CorteCaja::obtenerActivo($zona, $user->id);

        // Obtener pagos
        $query = Factura::whereNull('deleted_at')
            ->whereNotNull('numero_servicio')
            ->whereHas('cajero', function ($q) {
                $q->where('role', 'chivato');
            });

        // Si hay un corte activo, mostrar solo los pagos de ese corte
        if ($corteActivo) {
            $query->where('corte_caja_id', $corteActivo->id);
        } else {
            // Si no hay corte activo, mostrar pagos del día actual
            $query->whereDate('created_at', today());
        }

        // Aplicar filtro de fechas si se proporcionan (solo cuando no hay corte activo)
        if (! $corteActivo && $fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }
        if (! $corteActivo && $fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }

        $pagos = $query->orderByDesc('created_at')
            ->get(['id', 'reference_number', 'numero_servicio', 'periodo', 'total', 'payload', 'created_at', 'corte_caja_id']);

        $items = $pagos->map(function ($f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);

            // Calcular comisión por reconexión (solo si se cobró recargo en el recibo)
            $recargoCobrado = isset($payload['recargo']) && $payload['recargo'] === 'si';
            $comisionReconexion = $recargoCobrado ? 50 : 0;

            // Comisión por recibo cobrado ($10 por cada recibo)
            $comisionRecibo = 10;

            return [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'periodo' => $f->periodo,
                'total' => (float) $f->total - $comisionReconexion,
                'metodo' => $payload['metodo'] ?? ($payload['pago_metodo'] ?? '-'),
                'cobro' => $payload['cobro'] ?? '-',
                'nombre' => $payload['nombre'] ?? '-',
                'fecha' => $f->created_at ? $f->created_at->toDateTimeString() : null,
                'fecha_formateada' => $f->created_at ? $f->created_at->format('d/m/Y H:i') : null,
                'comision_recibo' => $comisionRecibo,
            ];
        });

        // Calcular totales de comisiones
        $totalComisionRecibo = $items->sum('comision_recibo');

        return view('chivato.corte', [
            'pagos' => $items,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'corteActivo' => $corteActivo,
            'totalComisionRecibo' => $totalComisionRecibo,
            'cobrador' => $user->name,
        ]);
    }

    public function historial(Request $request)
    {
        $pagos = Factura::withTrashed()
            ->whereNotNull('numero_servicio')
            ->whereHas('cajero', function ($q) {
                $q->where('role', 'chivato');
            })
            ->orderByDesc('created_at')
            ->get(['id', 'reference_number', 'numero_servicio', 'periodo', 'total', 'payload', 'created_at', 'deleted_at']);

        $items = $pagos->map(function ($f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
            $status = $f->deleted_at ? 'cancelado' : 'pagado';

            return [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'periodo' => $f->periodo,
                'total' => (float) $f->total,
                'metodo' => $payload['metodo'] ?? ($payload['pago_metodo'] ?? '-'),
                'cobro' => $payload['cobro'] ?? '-',
                'nombre' => $payload['nombre'] ?? '-',
                'fecha' => $f->created_at ? $f->created_at->toDateTimeString() : null,
                'fecha_formateada' => $f->created_at ? $f->created_at->format('d/m/Y H:i') : null,
                'status' => $status,
                'cancelado' => $f->deleted_at !== null,
            ];
        });

        return view('chivato.historial', ['pagos' => $items]);
    }

    public function eliminarPago(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);
        
        // Verificar que el pago pertenece a un cajero del perfil chivato
        $cajero = \App\Models\User::find($factura->created_by);
        if (!$cajero || $cajero->role !== 'chivato') {
            return redirect()->route('chivato.historial')->with('error', 'No tienes permiso para eliminar este pago.');
        }
        
        // Soft delete
        if ($factura->fingerprint) {
            $factura->fingerprint = substr($factura->fingerprint, 0, 40).'_can_'.now()->timestamp;
            $factura->save();
        }
        $factura->delete();
        
        return redirect()->route('chivato.historial')->with('success', 'Pago eliminado correctamente.');
    }

    /**
     * Historial de cortes de caja cerrados
     */
    public function historialCortes(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';

        // Obtener cortes cerrados del usuario actual
        $cortes = CorteCaja::where('zona', $zona)
            ->where('user_id', $user->id)
            ->where('estado', 'cerrado')
            ->orderByDesc('fecha_fin')
            ->get();

        // Verificar si hay corte activo (para mostrar o no el botón de reanudar)
        $corteActivo = CorteCaja::tieneActivo($zona, $user->id);

        // Obtener el primer corte (el más reciente) si existe
        $primerCorte = $cortes->first();

        return view('chivato.historial-cortes', [
            'cortes' => $cortes,
            'corteActivo' => $corteActivo,
            'primerCorte' => $primerCorte,
        ]);
    }

    // API Functions for Chivato Payments (Independent)

    public function prepaySettings(Request $request)
    {
        $rows = \App\Models\PrepaySetting::all()->pluck('enabled', 'paquete')->toArray();
        $defaults = [300 => true, 400 => true, 500 => true, 600 => true];
        $enabled = array_merge($defaults, $rows);
        $matrix = [
            6 => ['percent' => 10, 'totals' => [300 => 1620, 400 => 2160, 500 => 2700, 600 => 3240]],
            7 => ['percent' => 11, 'totals' => [300 => 1869, 400 => 2492, 500 => 3115, 600 => 3738]],
            8 => ['percent' => 12, 'totals' => [300 => 2112, 400 => 2816, 500 => 3520, 600 => 4224]],
            9 => ['percent' => 13, 'totals' => [300 => 2349, 400 => 3132, 500 => 3915, 600 => 4698]],
            10 => ['percent' => 14, 'totals' => [300 => 2580, 400 => 3440, 500 => 4300, 600 => 5160]],
            11 => ['percent' => 15, 'totals' => [300 => 2805, 400 => 3740, 500 => 4675, 600 => 5610]],
            12 => ['percent' => 16, 'totals' => [300 => 3024, 400 => 4032, 500 => 5040, 600 => 6048]],
        ];

        return response()->json(['ok' => true, 'enabled' => $enabled, 'matrix' => $matrix]);
    }

    public function recibosLookup(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $u = Usuario::with(['estado', 'estatusServicio'])->where('numero_servicio', $numero)->first();
        if (! $u) {
            return response()->json(['ok' => false, 'message' => 'No encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'numero_servicio' => $u->numero_servicio,
                'nombre_cliente' => $u->nombre_cliente,
                'domicilio' => $u->domicilio,
                'telefono' => $u->telefono,
                'paquete' => $u->paquete,
                'tarifa' => $u->tarifa,
                'primer_pago' => $u->primer_pago,
                'primer_pago_vencimiento' => $u->primer_pago_vencimiento,
                'fecha_contratacion' => $u->fecha_contratacion,
                'uso' => $u->uso,
                'tecnologia' => $u->tecnologia,
                'megas' => $u->megas,
                'estado' => optional($u->estado)->nombre,
                'estatus' => optional($u->estatusServicio)->nombre,
            ],
        ]);
    }

    public function recibosPagoAnterior(Request $request)
    {
        $numero = (string) $request->query('numero');
        $excludeId = $request->query('exclude_id');

        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }

        $f = Factura::where('numero_servicio', $numero)
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Sin pagos anteriores'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'monto' => (float) $f->total,
                'fecha' => $f->created_at->toIso8601String(),
                'created_at' => $f->created_at->toIso8601String(),
                'reference_number' => $f->reference_number,
                'periodo' => $f->periodo,
            ],
        ]);
    }

    public function recibosDeuda(Request $request, MorosidadService $service)
    {
        $numero = (string) $request->query('numero');
        $month = $request->query('month');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        if ($month !== null && ! preg_match('/^\d{4}\-\d{2}$/', (string) $month)) {
            $month = null;
        }
        $res = $service->calcularAdeudoUsuario($numero, $month);
        if (! ($res['ok'] ?? false)) {
            return response()->json($res, 404);
        }

        return response()->json($res);
    }

    public function recibosPrepayStatus(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $row = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $numero)
            ->where(function ($q) {
                $q->where('payload->prepay', 'si')
                    ->orWhere('payload->prepay', true);
            })
            ->orderByDesc('id')
            ->first(['id', 'numero_servicio', 'total', 'payload', 'created_at']);
        if (! $row) {
            return response()->json(['ok' => true, 'data' => ['activo' => false]]);
        }
        $p = is_array($row->payload) ? $row->payload : (is_string($row->payload) ? @json_decode($row->payload, true) : []);
        $months = (int) (($p['prepay_months'] ?? 0) ?: 0);
        $from = $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at) : null;
        $venceAt = PrepayDashboardService::venceAt($from, $months);
        $estado = PrepayDashboardService::estadoPorVencimiento($venceAt, now());
        $label = $venceAt ? $venceAt->locale('es')->translatedFormat('F Y') : null;

        return response()->json([
            'ok' => true,
            'data' => [
                'activo' => ! $estado['vencido'] && $venceAt !== null,
                'hasta_label' => $label,
                'hasta_periodo' => $venceAt ? $venceAt->format('Y-m') : null,
                'meses' => $months,
            ],
        ]);
    }

    public function recibosLayoutGet()
    {
        $setting = AppSetting::find('receipt_layout');

        return response()->json([
            'ok' => true,
            'layout' => $setting ? $setting->value : null,
        ]);
    }

    public function recibosFacturaStore(Request $request, MorosidadService $morosidadService, WhatsAppNotifierService $whatsapp)
    {
        $request->validate([
            'numero_servicio' => ['nullable', 'string'],
            'usuario_id' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric'],
            'payload' => ['nullable', 'array'],
        ]);

        // Verificar que haya un corte activo antes de permitir el pago
        $user = $request->user();
        $zona = 'chivato';
        
        if (!CorteCaja::tieneActivo($zona, $user->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se puede realizar el pago. No hay un corte de caja activo. Por favor, inicie un corte de caja antes de continuar.',
                'code' => 422
            ], 422);
        }

        return DB::transaction(function () use ($request, $morosidadService, $whatsapp) {
            $payloadInput = $request->input('payload', []);
            $periodoOverride = isset($payloadInput['periodo_override']) && preg_match('/^\d{4}-\d{2}$/', $payloadInput['periodo_override'])
                ? $payloadInput['periodo_override'] : null;
            $periodo = $periodoOverride
                ?? (!empty($payloadInput['mes_siguiente']) ? now()->addMonth()->format('Y-m') : now()->format('Y-m'));
            $numero = $request->input('numero_servicio');
            $usuarioId = $request->input('usuario_id');
            $user = $request->user();
            $zona = 'chivato';

            // Buscar corte activo para asociar el pago
            $corteActivo = CorteCaja::obtenerActivo($zona, $user->id);
            if ($numero) {
                $prepay = Factura::whereNull('deleted_at')
                    ->where('numero_servicio', $numero)
                    ->where(function ($q) {
                        $q->where('payload->prepay', 'si')
                            ->orWhere('payload->prepay', true);
                    })
                    ->orderByDesc('id')
                    ->first(['id', 'payload', 'created_at']);
                if ($prepay) {
                    $p = is_array($prepay->payload) ? $prepay->payload : (is_string($prepay->payload) ? @json_decode($prepay->payload, true) : []);
                    $months = (int) (($p['prepay_months'] ?? 0) ?: 0);
                    $from = $prepay->created_at ? \Illuminate\Support\Carbon::parse($prepay->created_at) : null;
                    $venceAt = PrepayDashboardService::venceAt($from, $months);
                    $estado = PrepayDashboardService::estadoPorVencimiento($venceAt, now());
                    if ($venceAt && ! $estado['vencido']) {
                        return response()->json([
                            'ok' => false,
                            'message' => 'Pago adelantado vigente hasta '.$venceAt->locale('es')->translatedFormat('F Y'),
                        ], 409);
                    }
                }
            }
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
                $row = (object) ['current_value' => 0];
            }
            $payload = $payloadInput;
            $fingerprintData = [
                'numero_servicio' => $request->input('numero_servicio'),
                'periodo' => $periodo,
                'total' => round((float) $request->input('total', 0), 2),
                'nombre' => $payload['nombre'] ?? null,
                'mensualidad' => $payload['mensualidad'] ?? null,
                'recargo' => $payload['recargo'] ?? null,
                'pago_anterior' => $payload['pago_anterior'] ?? null,
                'metodo' => $payload['metodo'] ?? 'Efectivo',
            ];
            $fingerprint = hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $existing = Factura::where(function ($q) use ($fingerprint) {
                $q->where('fingerprint', $fingerprint);
            })
                ->orWhere(function ($q) use ($request, $periodo) {
                    $q->where('numero_servicio', $request->input('numero_servicio'))
                        ->where('periodo', $periodo)
                        ->where('total', $request->input('total', 0))
                        ->whereRaw('payload = ?', [json_encode($request->input('payload', []))]);
                })
                ->orderBy('id', 'desc')
                ->first();
            if ($existing) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'success',
                    'reason' => 'Reimpresión / reuso de factura',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => true,
                    'referencia' => $existing->reference_number,
                    'id' => $existing->id,
                    'reused' => true,
                ]);
            }

            $trashedByFingerprint = Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
            if ($trashedByFingerprint) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'success',
                    'reason' => 'Reimpresión / reuso de factura (trashed)',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => true,
                    'referencia' => $trashedByFingerprint->reference_number,
                    'id' => $trashedByFingerprint->id,
                    'reused' => true,
                ]);
            }

            if (($numero !== null && $numero !== '') || ! empty($usuarioId)) {
                $dup = Factura::where('periodo', $periodo)
                    ->where(function ($q) use ($numero, $usuarioId) {
                        if ($numero !== null && $numero !== '') {
                            $q->where('numero_servicio', $numero);
                        }
                        if (! empty($usuarioId)) {
                            $q->orWhere('usuario_id', $usuarioId);
                        }
                    })
                    ->first();
            } else {
                $dup = null;
            }
            if ($dup) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'duplicate',
                    'reason' => 'Pago ya registrado para este periodo',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Ya existe un pago registrado para este periodo',
                    'referencia' => $dup->reference_number,
                    'periodo' => $periodo,
                ], 409);
            }
            $next = (int) $row->current_value + 1;
            DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->update(['current_value' => $next, 'updated_at' => now()]);

            // Estado de adeudo ANTES de registrar este pago: si el cliente ya
            // debía estar cortado por morosidad, se notifica a soporte para reactivar.
            $debiaCortarse = $numero ? $morosidadService->debeSerCortadoPorNumero($numero) : false;
            $nombreClienteNotif = $payload['nombre'] ?? null;

            try {
                $factura = new Factura;
                $factura->reference_number = $next;
                $factura->usuario_id = $request->input('usuario_id');
                $factura->numero_servicio = $request->input('numero_servicio');
                $factura->periodo = $periodo;
                $factura->total = $request->input('total', 0);
                $factura->payload = $payload;
                $factura->created_by = $request->user()?->id;
                $factura->fingerprint = $fingerprint;
                $factura->corte_caja_id = $corteActivo?->id; // Asociar al corte activo si existe
                $factura->save();

                if ($request->input('usuario_id')) {
                    $usuario = Usuario::find($request->input('usuario_id'));
                    if ($usuario) {
                        $usuario->update([
                            'estatus_servicio_id' => 1,
                            'estado_id' => 1,
                        ]);
                    }
                } elseif ($request->input('numero_servicio')) {
                    $usuario = Usuario::where('numero_servicio', $request->input('numero_servicio'))->first();
                    if ($usuario) {
                        $usuario->update([
                            'estatus_servicio_id' => 1,
                            'estado_id' => 1,
                        ]);
                    }
                }
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $c = Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
                if ($c) {
                    return response()->json([
                        'ok' => true,
                        'referencia' => $c->reference_number,
                        'id' => $c->id,
                        'reused' => true,
                    ]);
                }
                throw $e;
            }

            DB::table('payment_attempts')->insert([
                'usuario_id' => $usuarioId,
                'numero_servicio' => $numero,
                'periodo' => $periodo,
                'status' => 'success',
                'reason' => 'Factura creada',
                'created_by' => optional($request->user())->id,
                'payload' => json_encode($request->input('payload', [])),
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notificacionEnviada = false;
            if ($debiaCortarse && $numero) {
                $notificacionEnviada = $whatsapp->enviarNotificacionReactivacion($numero, $nombreClienteNotif);
            }

            return response()->json([
                'ok' => true,
                'referencia' => $factura->reference_number,
                'id' => $factura->id,
                'necesita_reactivacion' => $debiaCortarse,
                'notificacion_enviada' => $notificacionEnviada,
            ]);
        });
    }

    /**
     * Generar el ticket térmico como PDF (tamaño de página fijo en el archivo,
     * en vez de depender de que el navegador respete @page al imprimir HTML).
     */
    public function ticketPdf(Request $request)
    {
        $request->validate([
            'html' => ['required', 'string'],
        ]);

        // Incrustar las imágenes como base64 en vez de dejar que Dompdf las
        // descargue por HTTP: el servidor de desarrollo (php artisan serve)
        // es de un solo hilo y se bloquea a sí mismo si intenta atender esa
        // segunda petición mientras procesa esta.
        $html = strtr($request->input('html'), [
            asset('images/logo.png') => $this->imageToDataUri(public_path('images/logo.png')),
            asset('images/reportes.png') => $this->imageToDataUri(public_path('images/reportes.png')),
            asset('images/cuenta.png') => $this->imageToDataUri(public_path('images/cuenta.png')),
        ]);

        // Quitar el @page del HTML: Dompdf le da prioridad sobre setPaper(),
        // y "auto" no es una altura válida ahí, así que terminaba usando su
        // tamaño de página por defecto (carta) en vez del ancho de ticket.
        $html = preg_replace('/@page\s*\{[^}]*\}/i', '', $html);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // 80mm de ancho x 200mm de alto (holgado para que el ticket completo quepa en una sola página)
        $dompdf->setPaper([0, 0, 226.77, 566.93]);
        $dompdf->render();

        // Guardar el PDF y devolver una URL temporal firmada: la app Epson
        // TM Print Assistant (Android) descarga el archivo por su cuenta, sin
        // la sesión del navegador, así que la URL debe funcionar sin login.
        $dir = storage_path('app/tickets');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        foreach (glob($dir.'/*.pdf') ?: [] as $old) {
            if (filemtime($old) < now()->subHour()->getTimestamp()) {
                @unlink($old);
            }
        }
        $name = \Illuminate\Support\Str::random(40).'.pdf';
        file_put_contents($dir.'/'.$name, $dompdf->output());

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'chivato.recibos.ticket-pdf.show',
            now()->addMinutes(10),
            ['file' => $name]
        );

        return response()->json(['ok' => true, 'url' => $url]);
    }

    /**
     * Servir un ticket PDF generado previamente (URL firmada temporal,
     * consumida por el visor del navegador o por Epson TM Print Assistant).
     */
    public function ticketPdfShow(Request $request, string $file)
    {
        if (! preg_match('/^[A-Za-z0-9]{40}\.pdf$/', $file)) {
            abort(404);
        }
        $path = storage_path('app/tickets/'.$file);
        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket.pdf"',
        ]);
    }

    private function imageToDataUri(string $path): string
    {
        if (! file_exists($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * Iniciar un nuevo corte de caja
     */
    public function iniciarCorte(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';

        // Verificar si ya hay un corte activo
        if (CorteCaja::tieneActivo($zona, $user->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya tienes un corte de caja activo',
            ], 409);
        }

        // Crear nuevo corte
        $corte = CorteCaja::create([
            'user_id' => $user->id,
            'zona' => $zona,
            'fecha_inicio' => now(),
            'estado' => 'activo',
            'total_recaudado' => 0,
            'total_pagos' => 0,
        ]);

        return response()->json([
            'ok' => true,
            'corte' => [
                'id' => $corte->id,
                'fecha_inicio' => $corte->fecha_inicio->toDateTimeString(),
                'estado' => $corte->estado,
            ],
            'message' => 'Corte de caja iniciado correctamente',
        ]);
    }

    /**
     * Finalizar el corte de caja activo
     */
    public function finalizarCorte(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';

        // Buscar corte activo
        $corte = CorteCaja::obtenerActivo($zona, $user->id);

        if (! $corte) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes un corte de caja activo',
            ], 404);
        }

        // Calcular totales del corte
        $facturas = Factura::where('corte_caja_id', $corte->id)
            ->whereNull('deleted_at')
            ->get();

        $totalRecaudado = $facturas->sum(function($f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
            $recargo = (isset($payload['recargo']) && $payload['recargo'] === 'si') ? 50 : 0;
            return $f->total - $recargo;
        });
        $totalPagos = $facturas->count();

        // Actualizar corte
        $corte->update([
            'fecha_fin' => now(),
            'estado' => 'cerrado',
            'total_recaudado' => $totalRecaudado,
            'total_pagos' => $totalPagos,
        ]);

        return response()->json([
            'ok' => true,
            'corte' => [
                'id' => $corte->id,
                'fecha_inicio' => $corte->fecha_inicio->toDateTimeString(),
                'fecha_fin' => $corte->fecha_fin->toDateTimeString(),
                'estado' => 'cerrado',
                'total_recaudado' => $totalRecaudado,
                'total_pagos' => $totalPagos,
            ],
            'message' => 'Corte de caja finalizado correctamente',
        ]);
    }

    /**
     * Reanudar el último corte de caja cerrado (solo si no hay corte activo)
     */
    public function reanudarCorte(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';

        // Verificar que no haya un corte activo
        if (CorteCaja::tieneActivo($zona, $user->id)) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya tienes un corte de caja activo. No puedes reanudar otro.',
            ], 409);
        }

        // Buscar el último corte cerrado del usuario
        $ultimoCorte = CorteCaja::where('zona', $zona)
            ->where('user_id', $user->id)
            ->where('estado', 'cerrado')
            ->orderByDesc('fecha_fin')
            ->first();

        if (! $ultimoCorte) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay cortes cerrados para reanudar',
            ], 404);
        }

        // Reanudar el corte: cambiar estado a activo y limpiar fecha_fin
        $ultimoCorte->update([
            'estado' => 'activo',
            'fecha_fin' => null,
        ]);

        return response()->json([
            'ok' => true,
            'corte' => [
                'id' => $ultimoCorte->id,
                'fecha_inicio' => $ultimoCorte->fecha_inicio->toDateTimeString(),
                'estado' => 'activo',
            ],
            'message' => 'Corte de caja reanudado correctamente',
        ]);
    }

    /**
     * Verificar si hay un corte activo
     */
    public function corteActivo(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';

        $corte = CorteCaja::obtenerActivo($zona, $user->id);

        if (! $corte) {
            return response()->json([
                'ok' => true,
                'activo' => false,
                'message' => 'No hay corte activo',
            ]);
        }

        // Obtener pagos del corte actual
        $facturas = Factura::where('corte_caja_id', $corte->id)
            ->whereNull('deleted_at')
            ->get();

        return response()->json([
            'ok' => true,
            'activo' => true,
            'corte' => [
                'id' => $corte->id,
                'fecha_inicio' => $corte->fecha_inicio->toDateTimeString(),
                'estado' => $corte->estado,
                'total_recaudado' => $facturas->sum(function($f) {
                    $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                    $recargo = (isset($payload['recargo']) && $payload['recargo'] === 'si') ? 50 : 0;
                    return $f->total - $recargo;
                }),
                'total_pagos' => $facturas->count(),
            ],
        ]);
    }

    /**
     * Exportar pagos del corte activo a Excel
     */
    public function exportarCorteExcel(Request $request)
    {
        $user = $request->user();
        $zona = 'chivato';
        $zonaNombre = 'Chivato';

        // Buscar corte activo
        $corte = CorteCaja::obtenerActivo($zona, $user->id);

        if (! $corte) {
            return redirect()->route('chivato.corte')->with('error', 'No hay un corte activo para exportar.');
        }

        // Obtener pagos del corte
        $facturas = Factura::where('corte_caja_id', $corte->id)
            ->whereNull('deleted_at')
            ->with('cajero')
            ->orderBy('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="corte-caja-chivato-' . $corte->id . '-' . now()->format('Y-m-d') . '.xls"',
            'Cache-Control' => 'max-age=0',
        ];

        $callback = function () use ($facturas, $corte, $zonaNombre) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8
            echo '<html><head><meta charset="utf-8">';
            echo '<style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #888; padding: 6px 8px; font-family: Arial, Helvetica, sans-serif; font-size: 11pt; }
                thead th { background: #2e7d32; color: #fff; }
                .header-row { background: #1e3a8a; color: #fff; font-weight: bold; }
                .comision-row { background: #f59e0b; color: #fff; font-weight: bold; }
                .comision-recibo-row { background: #2563eb; color: #fff; font-weight: bold; }
                .total-entregar-row { background: #16a34a; color: #fff; font-weight: bold; }
                .text { mso-number-format: "\@"; }
                .date { mso-number-format: "dd/mm/yyyy\\ hh:mm"; }
                .money { mso-number-format: "\\$#,##0.00"; text-align: right; }
                .total-row td { background: #1e3a8a; color: #fff; font-weight: 700; }
                h2 { font-family: Arial, Helvetica, sans-serif; }
            </style></head><body>';

            // Información del corte
            echo '<h2>Reporte de Corte de Caja - ' . htmlspecialchars($zonaNombre) . '</h2>';
            echo '<table style="margin-bottom: 15px;">';
            echo '<tr class="header-row"><td colspan="2">Información del Corte</td></tr>';
            echo '<tr><td><strong>ID del Corte:</strong></td><td class="text">' . htmlspecialchars((string) $corte->id) . '</td></tr>';
            echo '<tr><td><strong>Fecha de Inicio:</strong></td><td>' . $corte->fecha_inicio->format('d/m/Y H:i:s') . '</td></tr>';
            echo '<tr><td><strong>Total de Pagos:</strong></td><td>' . $facturas->count() . '</td></tr>';
            echo '<tr><td><strong>Total Recaudado:</strong></td><td class="money">' . number_format($facturas->sum('total'), 2, '.', '') . '</td></tr>';
            $totalRecaudado = $facturas->sum(function($f) {
                $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                $recargo = (isset($payload['recargo']) && $payload['recargo'] === 'si') ? 50 : 0;
                return $f->total - $recargo;
            });
            $totalComisionRecibo = $facturas->count() * 10;
            $totalAEntregar = $totalRecaudado - $totalComisionRecibo;

            echo '<tr class="comision-recibo-row"><td><strong>Comisión por Recibo ($10 c/u):</strong></td><td class="money">' . number_format($totalComisionRecibo, 2, '.', '') . '</td></tr>';
            echo '<tr class="total-entregar-row"><td><strong>TOTAL A ENTREGAR:</strong></td><td class="money">' . number_format($totalAEntregar, 2, '.', '') . '</td></tr>';
            echo '</table>';

            // Tabla de pagos
            echo '<table>';
            echo '<thead><tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>No. Servicio</th>
                <th>Cliente</th>
                <th>Periodo</th>
                <th>Monto</th>
                <th>Método de Pago</th>
                <th>Quién Cobró</th>
                <th>Comisión Recibo</th>
            </tr></thead><tbody>';

            foreach ($facturas as $f) {
                $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                $nombre = is_array($payload) ? ($payload['nombre'] ?? '-') : '-';
                $metodo = is_array($payload) ? ($payload['metodo'] ?? ($payload['pago_metodo'] ?? '-')) : '-';
                $cobro = is_array($payload) ? ($payload['cobro'] ?? '-') : '-';
                $folio = str_pad((string) $f->reference_number, 8, '0', STR_PAD_LEFT);

                // Calcular comisión por reconexión (solo si hay recargo en el pago)
                $recargoCobrado = isset($payload['recargo']) && $payload['recargo'] === 'si';

                // Mostrar el monto sin el recargo
                $montoMostrar = $f->total - ($recargoCobrado ? 50 : 0);

                echo '<tr>';
                echo '<td class="text">' . htmlspecialchars($folio) . '</td>';
                echo '<td class="date">' . htmlspecialchars($f->created_at->format('d/m/Y H:i:s')) . '</td>';
                echo '<td class="text">' . htmlspecialchars((string) $f->numero_servicio) . '</td>';
                echo '<td>' . htmlspecialchars($nombre) . '</td>';
                echo '<td class="text">' . htmlspecialchars($f->periodo) . '</td>';
                echo '<td class="money">' . number_format($montoMostrar, 2, '.', '') . '</td>';
                echo '<td>' . htmlspecialchars($metodo) . '</td>';
                echo '<td>' . htmlspecialchars($cobro) . '</td>';
                echo '<td class="money">$10.00</td>';
                echo '</tr>';
            }

            // Fila de totales
            echo '</tbody><tfoot><tr class="total-row">';
            echo '<td class="text"></td>';
            echo '<td colspan="4" style="text-align: right;">TOTAL RECAUDADO:</td>';
            echo '<td class="money">' . number_format($totalRecaudado, 2, '.', '') . '</td>';
            echo '<td colspan="2"></td>';
            echo '<td class="money">' . number_format($facturas->count() * 10, 2, '.', '') . '</td>';
            echo '</tr></tfoot></table>';

            echo '</body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }
}
