<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Usuario;
use App\Models\CargoMora;
use Illuminate\Support\Carbon;

class MorosidadService
{
    public function calcularAdeudoUsuario(string $numeroServicio, ?string $periodo = null): array
    {
        $numero = (string) $numeroServicio;
        if ($numero === '') {
            return ['ok' => false, 'message' => 'Número inválido'];
        }
        $usuario = Usuario::where('numero_servicio', $numero)->first();
        if (!$usuario) {
            return ['ok' => false, 'message' => 'Usuario no encontrado'];
        }
        $periodo = $periodo ?: now()->format('Y-m');
        $curStart = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $dueDate = $curStart->copy()->day(7)->endOfDay();
        $today = now();
        $mensualidad = (float) preg_replace('/[^\d.]/', '', (string) ($usuario->tarifa ?? 0));
        if (!is_finite($mensualidad) || $mensualidad < 0) {
            $mensualidad = 0.0;
        }
        $ultimoPagoPeriodo = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $numero)
            ->orderByDesc('periodo')
            ->value('periodo');
        $mesesAdeudo = 1;
        $desdePeriodo = $periodo;
        if ($ultimoPagoPeriodo) {
            $lp = Carbon::createFromFormat('Y-m', $ultimoPagoPeriodo)->startOfMonth();
            $diff = $lp->diffInMonths($curStart);
            $mesesAdeudo = max(1, $diff);
            if ($lp->lessThan($curStart)) {
                $desdePeriodo = $lp->copy()->addMonth()->format('Y-m');
            }
        }
        $recargo = ($today->day >= 8 && $mesesAdeudo >= 1) ? 50.0 : 0.0;
        $moraRow = CargoMora::where('periodo', $periodo)->where('numero_servicio', $numero)->first();
        if ($moraRow) {
            $recargo = max($recargo, (float) $moraRow->monto);
        }
        $base = max(0.0, $mensualidad * $mesesAdeudo);
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
        $pendiente = round(max(0.0, $base - $pagadoParcial) + $recargo, 2);
        $desdeMes = Carbon::createFromFormat('Y-m', $desdePeriodo)->translatedFormat('F Y');
        return [
            'ok' => true,
            'numero' => $numero,
            'mensualidad' => round($mensualidad, 2),
            'meses_adeudo' => $mesesAdeudo,
            'desde_periodo' => $desdePeriodo,
            'desde_mes_label' => $desdeMes,
            'recargo' => round($recargo, 2),
            'pagado_parcial' => round($pagadoParcial, 2),
            'pendiente' => $pendiente,
            'vencimiento' => $dueDate->toDateString(),
        ];
    }
}

