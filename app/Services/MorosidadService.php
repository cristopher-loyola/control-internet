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
        $dueDate = $curStart->copy()->day(7)->endOfDay();
        $today = now();
        $mensualidad = (float) preg_replace('/[^\d.]/', '', (string) ($usuario->tarifa ?? 0));
        if (! is_finite($mensualidad) || $mensualidad < 0) {
            $mensualidad = 0.0;
        }

        $ultimoPeriodoPagoSuficiente = $this->ultimoPeriodoConPagoSuficiente($numero, $mensualidad, $periodo);
        $ultimoPeriodoPrepay = $this->ultimoPeriodoCubiertoPorPrepay($numero, $mensualidad);
        $ultimoPeriodoCubierto = $this->maxPeriodo($ultimoPeriodoPagoSuficiente, $ultimoPeriodoPrepay);

        $mesesAdeudo = 0;
        $desdePeriodo = $periodo;
        if ($ultimoPeriodoCubierto) {
            $lp = Carbon::createFromFormat('Y-m', $ultimoPeriodoCubierto)->startOfMonth();
            if ($lp->lessThan($curStart)) {
                $mesesAdeudo = $lp->diffInMonths($curStart);
                $desdePeriodo = $lp->copy()->addMonth()->format('Y-m');
            }
        } else {
            $mesesAdeudo = 1;
            $desdePeriodo = $periodo;
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
        $desdeMes = Carbon::createFromFormat('Y-m', $desdePeriodo)->translatedFormat('F Y');
        $hastaMes = $curStart->translatedFormat('F Y');

        return [
            'ok' => true,
            'numero' => $numero,
            'mensualidad' => round($mensualidad, 2),
            'meses_adeudo' => (int) $mesesAdeudo,
            'desde_periodo' => $desdePeriodo,
            'desde_mes_label' => $desdeMes,
            'hasta_periodo' => $periodo,
            'hasta_mes_label' => $hastaMes,
            'ultimo_periodo_cubierto' => $ultimoPeriodoCubierto,
            'recargo' => round($recargo, 2),
            'pagado_parcial' => round($pagadoParcial, 2),
            'pendiente' => $pendiente,
            'vencimiento' => $dueDate->toDateString(),
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
