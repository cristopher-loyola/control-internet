<?php

namespace App\Services;

use App\Models\CargoMora;
use App\Models\Factura;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MorosidadService
{
    /**
     * Último mes (YYYY-MM) cubierto por el adeudo_monto importado del Excel.
     * A partir del mes siguiente, la deuda de clientes con adeudo manual se
     * acumula mes a mes contra la mensualidad. Importación única de junio 2026.
     */
    private const ADEUDO_IMPORT_CUTOFF = '2026-06';

    /**
     * Calcula el adeudo del cliente para un periodo (YYYY-MM).
     *
     * Flujo (corregido para pagos por adelantado):
     * - Determina la mensualidad del cliente.
     * - Calcula el último periodo efectivamente cubierto:
     *   - Último periodo con pago suficiente (suma de facturas del periodo >= mensualidad).
     *   - Si existen pagos por adelantado, extiende la cobertura hasta el periodo final (periodo_factura + prepay_months).
     * - Si el periodo consultado está dentro de la cobertura, el adeudo es 0 (sin recargo).
     * - Si no, calcula meses en adeudo desde el mes posterior al último cubierto hasta el periodo consultado (inclusive),
     *   aplica recargo (si corresponde) y descuenta pagos parciales realizados en esos periodos.
     */
    public function calcularAdeudoUsuario(string $numeroServicio, ?string $periodo = null): array
    {
        $numero = (string) $numeroServicio;
        if ($numero === '') {
            return ['ok' => false, 'message' => 'Número inválido'];
        }
        $usuario = Usuario::where('numero_servicio', $numero)->first();
        if (! $usuario) {
            return ['ok' => false, 'message' => 'Usuario no encontrado'];
        }
        $periodo = $periodo ?: now()->format('Y-m');
        $curStart = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $today = now();

        // Determinar si es el primer periodo de cobro (hasta la fecha de vencimiento del primer pago)
        $esPrimerPeriodo = false;
        $primerPago = (float) ($usuario->primer_pago ?? 0);
        $vencimientoPrimerPago = $usuario->primer_pago_vencimiento;
        if ($primerPago > 0 && $vencimientoPrimerPago) {
            // Es primer periodo si estamos antes o en la fecha de vencimiento
            $esPrimerPeriodo = $today->lessThanOrEqualTo(Carbon::parse($vencimientoPrimerPago)->endOfDay());
        }

        // Usar primer_pago como mensualidad si es el primer periodo
        $mensualidad = $esPrimerPeriodo
            ? $primerPago
            : (float) preg_replace('/[^\d.]/', '', (string) ($usuario->tarifa ?? 0));

        // Fecha de vencimiento: día 7 del mes de cobro
        $dueDate = $curStart->copy()->day(7)->endOfDay();
        if (! is_finite($mensualidad) || $mensualidad < 0) {
            $mensualidad = 0.0;
        }

        $ultimoPeriodoPagoSuficiente = $this->ultimoPeriodoConPagoSuficiente($numero, $mensualidad, $periodo);
        $ultimoPeriodoPrepay = $this->ultimoPeriodoCubiertoPorPrepay($numero, $mensualidad);
        $ultimoPeriodoCubierto = $this->maxPeriodo($ultimoPeriodoPagoSuficiente, $ultimoPeriodoPrepay);

        // Extender cobertura con proximo_pago aunque ya haya facturas (ej. adelanto/transferencia registrado en Excel).
        // proximo_pago = "2026-08" significa que julio ya está cubierto → último cubierto = "2026-07".
        if (!empty($usuario->proximo_pago) && preg_match('/^\d{4}-\d{2}$/', (string) $usuario->proximo_pago)) {
            try {
                $ppLastCovered = Carbon::createFromFormat('Y-m', $usuario->proximo_pago)
                    ->startOfMonth()->subMonth()->format('Y-m');
                $ultimoPeriodoCubierto = $this->maxPeriodo($ultimoPeriodoCubierto, $ppLastCovered);
            } catch (\Throwable $e) {}
        }

        $mesesAdeudo = 0;
        $desdePeriodo = $periodo;
        if ($ultimoPeriodoCubierto) {
            $lp = Carbon::createFromFormat('Y-m', $ultimoPeriodoCubierto)->startOfMonth();
            if ($lp->lessThan($curStart)) {
                $mesesAdeudo = $lp->diffInMonths($curStart);
                $desdePeriodo = $lp->copy()->addMonth()->format('Y-m');
            }
        } else {
            // Sin facturas (ej. importado de Excel). Inferir desde cuándo debe.
            if (!empty($usuario->proximo_pago) && preg_match('/^\d{4}-\d{2}$/', $usuario->proximo_pago)) {
                // proximo_pago indica el primer período impago; el mes anterior fue el último cubierto.
                try {
                    $proxPago = Carbon::createFromFormat('Y-m', $usuario->proximo_pago)->startOfMonth();
                    $lp = $proxPago->copy()->subMonth();
                    if ($lp->lessThan($curStart)) {
                        $mesesAdeudo = $lp->diffInMonths($curStart);
                        $desdePeriodo = $lp->copy()->addMonth()->format('Y-m');
                    } else {
                        $mesesAdeudo = 0;
                        $desdePeriodo = $periodo;
                    }
                } catch (\Throwable $e) {
                    $mesesAdeudo = 1;
                    $desdePeriodo = $periodo;
                }
            } elseif (!empty($usuario->adeudo_descripcion)) {
                // Intentar extraer el período de inicio desde la descripción (ej. "Adeuda mayo 2026").
                $parsedPeriodo = $this->parsePeriodoFromDescripcion((string) $usuario->adeudo_descripcion);
                if ($parsedPeriodo) {
                    try {
                        $descStart = Carbon::createFromFormat('Y-m', $parsedPeriodo)->startOfMonth();
                        if ($descStart->lessThanOrEqualTo($curStart)) {
                            $desdePeriodo = $parsedPeriodo;
                            $mesesAdeudo = $descStart->diffInMonths($curStart->copy()->addMonth());
                        } else {
                            $mesesAdeudo = 1;
                            $desdePeriodo = $periodo;
                        }
                    } catch (\Throwable $e) {
                        $mesesAdeudo = 1;
                        $desdePeriodo = $periodo;
                    }
                } else {
                    $mesesAdeudo = 1;
                    $desdePeriodo = $periodo;
                }
            } else {
                $mesesAdeudo = 1;
                $desdePeriodo = $periodo;
            }
        }

        $recargo = ($today->day >= 8 && $mesesAdeudo >= 1) ? 50.0 : 0.0;
        $moraRow = CargoMora::where('periodo', $periodo)->where('numero_servicio', $numero)->first();
        if ($moraRow) {
            $recargo = max($recargo, (float) $moraRow->monto);
        }
        if ($mesesAdeudo <= 0) {
            $recargo = 0.0;
        }

        $base = max(0.0, $mensualidad * max(0, $mesesAdeudo));
        $pagadoParcial = 0.0;
        try {
            $from = Carbon::createFromFormat('Y-m', $desdePeriodo)->startOfMonth();
            $to = $curStart->copy()->endOfMonth();
            $pagadoParcial = (float) Factura::whereNull('deleted_at')
                ->where('numero_servicio', $numero)
                ->whereBetween('periodo', [$from->format('Y-m'), $to->format('Y-m')])
                ->sum('total');
        } catch (\Throwable $e) {
            $pagadoParcial = 0.0;
        }
        $pendiente = round(max(0.0, ($base + $recargo) - $pagadoParcial), 2);

        if ($usuario->adeudo_monto > 0) {
            $montoManual = (float) $usuario->adeudo_monto;

            $proxPagoCovers = !empty($usuario->proximo_pago)
                && preg_match('/^\d{4}-\d{2}$/', (string) $usuario->proximo_pago)
                && strcmp((string) $usuario->proximo_pago, $periodo) > 0;

            if ($proxPagoCovers) {
                $pendiente = round(max(0.0, $montoManual - $pagadoParcial), 2);
                $recargo = 0.0;
                $mesesAdeudo = 1;
                $desdePeriodo = $periodo;
            } else {
                // montoManual (adeudo_monto) = deuda manual importada del Excel; representa TODO
                // el adeudo ACUMULADO hasta el mes de corte de importación (junio 2026, incl.).
                // Los meses VIVOS (julio 2026 en adelante) se acumulan a la mensualidad, restando
                // los pagos hechos en esos meses. Así la deuda crece mes a mes sin congelarse y
                // sin inventar meses intermedios (la descripción solo indica el mes inicial).
                // montoManual ya está NETO de pagos al adeudo (marcarComoPagado lo reduce).
                $primerMesVivo = Carbon::createFromFormat('Y-m', self::ADEUDO_IMPORT_CUTOFF)->startOfMonth()->addMonth();

                $extraMonths = 0;
                if ($primerMesVivo->lessThanOrEqualTo($curStart)) {
                    $extraMonths = $primerMesVivo->diffInMonths($curStart) + 1;
                }
                $pagoExtra = 0.0;
                if ($extraMonths > 0) {
                    $pagoExtra = (float) Factura::whereNull('deleted_at')
                        ->where('numero_servicio', $numero)
                        ->whereBetween('periodo', [$primerMesVivo->format('Y-m'), $periodo])
                        ->sum('total');
                }
                $extra = max(0.0, ($mensualidad * $extraMonths) - $pagoExtra);
                $pendiente = round(max(0.0, $montoManual + $recargo + $extra), 2);

                $mesesAdeudo = max(1, $extraMonths);
                $desdePeriodo = $extraMonths > 0 ? $primerMesVivo->format('Y-m') : $periodo;
            }
        }

        // Recargo por pago tardío del periodo actual:
        // Solo aplica si la mensualidad NO se cubrió a tiempo (antes/en el día 7 de vencimiento).
        // Si pagó completo y puntual, no debe recargo aunque hoy sea día >= 8.
        if ($usuario->adeudo_monto <= 0 && $mesesAdeudo <= 0) {
            $limiteSinRecargo = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth()->day(7)->endOfDay();
            $pagadoATiempo = (float) Factura::whereNull('deleted_at')
                ->where('numero_servicio', $numero)
                ->where('periodo', $periodo)
                ->where('created_at', '<=', $limiteSinRecargo)
                ->sum('total');

            // Hubo pago tardío/incompleto solo si al vencer no se había cubierto la mensualidad
            if ($pagadoATiempo < $mensualidad - 0.01) {
                $recargoActual = ($today->day >= 8) ? 50.0 : 0.0;
                $moraRowActual = CargoMora::where('periodo', $periodo)->where('numero_servicio', $numero)->first();
                if ($moraRowActual) {
                    $recargoActual = max($recargoActual, (float) $moraRowActual->monto);
                }
                if ($recargoActual > 0) {
                    $pagadoPeriodoActual = (float) Factura::whereNull('deleted_at')
                        ->where('numero_servicio', $numero)
                        ->where('periodo', $periodo)
                        ->sum('total');
                    $dueActual = $mensualidad + $recargoActual;
                    // Solo cuando hubo pago del mes pero no alcanzó a cubrir mensualidad + recargo
                    if ($pagadoPeriodoActual > 0 && $pagadoPeriodoActual < $dueActual - 0.01) {
                        $faltante = round($dueActual - $pagadoPeriodoActual, 2);
                        $pendiente = round($pendiente + $faltante, 2);
                        $recargo = $recargoActual;
                        $mesesAdeudo = 1;
                        $desdePeriodo = $periodo;
                    }
                }
            }
        }

        // Detectar si el cliente está cubierto este mes sin deuda (pagó por transferencia/adelanto).
        // Solo se requiere proximo_pago en el futuro; la descripción puede ser vacía.
        $cubiertoEsteMes = (
            $mesesAdeudo <= 0
            && (float) $pendiente <= 0
            && !empty($usuario->proximo_pago)
            && strcmp($usuario->proximo_pago, $periodo) > 0
        );

        $desdeMes = $usuario->adeudo_descripcion ?: Carbon::createFromFormat('Y-m', $desdePeriodo)->locale('es')->translatedFormat('F Y');
        $hastaMes = $curStart->locale('es')->translatedFormat('F Y');

        $listaMeses = [];
        // REQUERIMIENTO: Si el usuario tiene adeudo manual (importado de Excel), lo incluimos en la lista.
        if ($usuario->adeudo_monto > 0) {
            $listaMeses[] = $usuario->adeudo_descripcion ?: 'Adeudo anterior';
        }
        
        // Si NO tiene adeudo manual o además tiene adeudos por meses, los incluimos
        if ($mesesAdeudo > 0) {
            $temp = Carbon::createFromFormat('Y-m', $desdePeriodo)->startOfMonth();
            for ($i = 0; $i < $mesesAdeudo; $i++) {
                if ($temp->format('Y-m') !== $periodo) {
                    $listaMeses[] = $temp->locale('es')->translatedFormat('F Y');
                }
                $temp->addMonth();
            }
        }

        return [
            'ok' => true,
            'numero' => $numero,
            'mensualidad' => round($mensualidad, 2),
            'es_primer_periodo' => $esPrimerPeriodo,
            'meses_adeudo' => (int) $mesesAdeudo, // Sin ajuste visual, solo meses reales del calendario
            'lista_meses' => $listaMeses,
            'desde_periodo' => $desdePeriodo,
            'desde_mes_label' => $desdeMes,
            'hasta_periodo' => $periodo,
            'hasta_mes_label' => $hastaMes,
            'ultimo_periodo_cubierto' => $ultimoPeriodoCubierto,
            'recargo' => round($recargo, 2),
            'pagado_parcial' => round($pagadoParcial, 2),
            'pendiente' => $pendiente,
            'vencimiento' => $dueDate->toDateString(),
            'adeudo_manual' => (float) $usuario->adeudo_monto,
            'descripcion_manual' => $usuario->adeudo_descripcion,
            'cubierto_este_mes' => $cubiertoEsteMes,
        ];
    }

    private function ultimoPeriodoConPagoSuficiente(string $numeroServicio, float $mensualidad, string $periodoHasta): ?string
    {
        $query = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $numeroServicio)
            ->whereNotNull('periodo')
            ->where('periodo', '<=', $periodoHasta)
            ->select('periodo', DB::raw('SUM(total) as total_sum'))
            ->groupBy('periodo')
            ->orderByDesc('periodo');

        if ($mensualidad <= 0) {
            return $query->value('periodo');
        }

        foreach ($query->get() as $row) {
            $p = (string) ($row->periodo ?? '');
            if ($p === '') {
                continue;
            }
            $sum = (float) ($row->total_sum ?? 0);
            if ($sum >= $mensualidad) {
                return $p;
            }
        }

        return null;
    }

    private function ultimoPeriodoCubiertoPorPrepay(string $numeroServicio, float $mensualidad): ?string
    {
        $facturas = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $numeroServicio)
            ->whereNotNull('periodo')
            ->where(function ($q) {
                $q->where('payload->prepay', 'si')
                    ->orWhere('payload->prepay', true);
            })
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->get(['periodo', 'payload']);

        $max = null;
        foreach ($facturas as $f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
            $monthsDeclared = (int) ($payload['prepay_months'] ?? 0);
            if ($monthsDeclared <= 0) {
                continue;
            }
            $prepayPaid = (float) ($payload['prepay_total'] ?? 0);
            $monthsEffective = $monthsDeclared;
            if ($mensualidad > 0 && $prepayPaid > 0) {
                $monthsEffective = $this->prepayEffectiveMonths($mensualidad, $monthsDeclared, $prepayPaid);
            }
            if ($monthsEffective <= 0) {
                continue;
            }
            try {
                $end = Carbon::createFromFormat('Y-m', (string) $f->periodo)->startOfMonth()->addMonths($monthsEffective)->format('Y-m');
                $max = $this->maxPeriodo($max, $end);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $max;
    }

    private function prepayEffectiveMonths(float $mensualidad, int $monthsDeclared, float $prepayPaid): int
    {
        $max = 0;
        for ($m = 1; $m <= $monthsDeclared; $m++) {
            $expected = $this->prepayExpectedTotal($mensualidad, $m);
            if ($expected <= 0) {
                continue;
            }
            if ($expected <= $prepayPaid + 0.00001) {
                $max = $m;
            }
        }

        return $max;
    }

    private function prepayExpectedTotal(float $mensualidad, int $months): float
    {
        $mens = (int) round($mensualidad);
        if ($months <= 0) {
            return 0.0;
        }
        if ($months <= 5) {
            return round($mensualidad * $months, 2);
        }
        $matrix = [
            6 => ['percent' => 10, 'totals' => [300 => 1620, 400 => 2160, 500 => 2700, 600 => 3240]],
            7 => ['percent' => 11, 'totals' => [300 => 1869, 400 => 2492, 500 => 3115, 600 => 3738]],
            8 => ['percent' => 12, 'totals' => [300 => 2112, 400 => 2816, 500 => 3520, 600 => 4224]],
            9 => ['percent' => 13, 'totals' => [300 => 2349, 400 => 3132, 500 => 3915, 600 => 4698]],
            10 => ['percent' => 14, 'totals' => [300 => 2580, 400 => 3440, 500 => 4300, 600 => 5160]],
            11 => ['percent' => 15, 'totals' => [300 => 2805, 400 => 3740, 500 => 4675, 600 => 5610]],
            12 => ['percent' => 16, 'totals' => [300 => 3024, 400 => 4032, 500 => 5040, 600 => 6048]],
        ];
        $info = $matrix[$months] ?? null;
        if ($info && isset($info['totals'][$mens])) {
            return (float) $info['totals'][$mens];
        }
        $percent = (float) ($info['percent'] ?? 0);
        $base = $mensualidad * $months;

        return round($base * (1 - ($percent / 100)), 2);
    }

    /**
     * Determina si un usuario debe estar cortado según su adeudo, con la
     * misma regla que usa la pantalla de Cortes: adeudo de un periodo
     * anterior + ya pasó el día 7 del mes actual.
     */
    public function debeSerCortado(Usuario $usuario, array $adeudo, string $mesActual, int $diaDelMes): bool
    {
        $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
        $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;

        $originalPagado = true;
        if ($mesesAdeudo == 0) {
            $originalPagado = true;
        } elseif ($mesesAdeudo == 1 && $desdePeriodo === $mesActual) {
            $originalPagado = true;
        } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes < 8) {
            $originalPagado = true;
        } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes >= 8) {
            $originalPagado = false;
        } else {
            $originalPagado = true;
        }

        if (! $originalPagado) {
            return true;
        }

        // Adeudo manual (importado)
        if ($usuario->adeudo_monto <= 0) {
            return false;
        }

        $parsedPeriodo = $this->parsePeriodoFromDescripcion($usuario->adeudo_descripcion ?? '');
        if ($parsedPeriodo && $parsedPeriodo < $mesActual) {
            return true;
        }

        if (! empty($usuario->proximo_pago) && preg_match('/^\d{4}-\d{2}$/', $usuario->proximo_pago)) {
            if ($usuario->proximo_pago < $mesActual) {
                return true;
            }
        }

        return false;
    }

    /**
     * Aplica un pago sobre el adeudo manual (importado) de un usuario: si el
     * pago cubre el adeudo completo lo limpia, si es parcial lo reduce.
     * Guarda el valor previo dentro del payload de la factura
     * (adeudo_monto_previo / adeudo_descripcion_previa) para que, si la
     * factura se cancela después, se pueda restaurar (mismo patrón que
     * FacturaService::marcarComoPagado, usado por Admin/Pagos).
     *
     * Devuelve ['payload' => array, 'adeudo_monto' => float, 'adeudo_descripcion' => ?string].
     * Si no había adeudo manual o no se pagó nada, regresa el payload intacto.
     */
    public function aplicarPagoAAdeudoManual(Usuario $usuario, float $totalPagado, array $payload): array
    {
        $adeudoMonto = (float) ($usuario->adeudo_monto ?? 0);

        if ($adeudoMonto <= 0 || $totalPagado <= 0) {
            return [
                'payload' => $payload,
                'adeudo_monto' => $usuario->adeudo_monto,
                'adeudo_descripcion' => $usuario->adeudo_descripcion,
            ];
        }

        $payload['adeudo_monto_previo'] = $adeudoMonto;
        $payload['adeudo_descripcion_previa'] = $usuario->adeudo_descripcion;

        $cubreTodo = $totalPagado >= $adeudoMonto - 0.01;
        $restante = $cubreTodo ? 0 : round($adeudoMonto - $totalPagado, 2);

        return [
            'payload' => $payload,
            'adeudo_monto' => $restante > 0 ? $restante : 0,
            'adeudo_descripcion' => $restante > 0 ? $usuario->adeudo_descripcion : null,
        ];
    }

    /**
     * Atajo: calcula el adeudo y evalúa debeSerCortado() para un número de
     * servicio, usando el estado actual (antes de registrar un nuevo pago).
     */
    public function debeSerCortadoPorNumero(string $numeroServicio): bool
    {
        $usuario = Usuario::where('numero_servicio', (string) $numeroServicio)->first();
        if (! $usuario) {
            return false;
        }

        $adeudo = $this->calcularAdeudoUsuario((string) $numeroServicio);

        return $this->debeSerCortado($usuario, $adeudo, now()->format('Y-m'), now()->day);
    }

    public function parsePeriodoFromDescripcion(string $desc): ?string
    {
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];
        $pattern = '/(' . implode('|', array_keys($meses)) . ')\s+(\d{4})/i';
        if (preg_match($pattern, strtolower($desc), $m)) {
            return sprintf('%04d-%02d', (int) $m[2], $meses[$m[1]]);
        }

        // Intento secundario: buscar mes sin año (asumir año actual si es adeudo manual)
        $patternMes = '/(' . implode('|', array_keys($meses)) . ')/i';
        if (preg_match($patternMes, strtolower($desc), $m)) {
            return sprintf('%04d-%02d', (int) date('Y'), $meses[$m[1]]);
        }

        return null;
    }

    private function maxPeriodo(?string $a, ?string $b): ?string
    {
        if (! $a) {
            return $b;
        }
        if (! $b) {
            return $a;
        }
        try {
            $ca = Carbon::createFromFormat('Y-m', $a)->startOfMonth();
            $cb = Carbon::createFromFormat('Y-m', $b)->startOfMonth();

            return $cb->greaterThan($ca) ? $b : $a;
        } catch (\Throwable $e) {
            return $a ?: $b;
        }
    }
}
