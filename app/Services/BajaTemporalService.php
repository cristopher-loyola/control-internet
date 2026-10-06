<?php

namespace App\Services;

use App\Models\EstatusServicio;
use App\Models\Factura;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BajaTemporalService
{
    public function permiteReanudar(?Usuario $usuario): bool
    {
        return $usuario
            && mb_strtolower((string) $usuario->estatusServicio?->nombre) === 'baja temporal'
            && (string) $usuario->proximo_pago > now()->format('Y-m');
    }

    public function informacionReanudacion(string $numero): array
    {
        $usuario = Usuario::where('numero_servicio', $numero)->first();
        return [
            'puede_reanudar_baja' => $this->permiteReanudar($usuario),
            'mensualidad_reanudacion' => (float) ($usuario?->tarifa ?? 0),
        ];
    }
    public function inicio(string $numero): Carbon
    {
        $inicio = now()->startOfMonth();
        $adelantos = Factura::where('numero_servicio', $numero)
            ->where(fn ($q) => $q->where('payload->prepay', 'si')->orWhere('payload->prepay', true))
            ->get();

        foreach ($adelantos as $factura) {
            $p = $factura->payload ?? [];
            $vence = PrepayDashboardService::venceAt(
                $factura->created_at,
                (int) ($p['prepay_months'] ?? 0),
                ($p['prepay_next_month'] ?? false) === true
            );
            if ($vence && $vence->gte($inicio)) {
                $inicio = $vence->copy()->addDay()->startOfMonth();
            }
        }

        return $inicio;
    }

    public function activarProgramadas(): void
    {
        Factura::where('payload->otro', 'baja_temporal')
            ->where('payload->baja_temporal_programada', true)
            ->where('payload->baja_temporal_desde', '<=', now()->toDateString())
            ->each(function (Factura $factura) {
                DB::transaction(function () use ($factura) {
                    $factura = Factura::whereKey($factura->id)->lockForUpdate()->first();
                    if (! $factura || empty($factura->payload['baja_temporal_programada'])) {
                        return;
                    }
                    $p = $factura->payload;
                    $usuario = Usuario::where('numero_servicio', $factura->numero_servicio)->lockForUpdate()->first();
                    // Una cancelación o un cambio posterior de cobertura invalida la programación.
                    if ($usuario && $p['baja_temporal_hasta'] > now()->toDateString()
                        && mb_strtolower((string) $usuario->estatusServicio?->nombre) !== 'cancelado'
                        && $usuario->proximo_pago === substr($p['baja_temporal_hasta'], 0, 7)
                        && ! Factura::where('numero_servicio', $factura->numero_servicio)
                            ->where('id', '>', $factura->id)->where('payload->otro', 'cancelacion')->exists()) {
                        $baja = EstatusServicio::whereRaw('LOWER(nombre) = ?', ['baja temporal'])->first()
                            ?? EstatusServicio::create(['nombre' => 'Baja temporal']);
                        $usuario->update(['estatus_servicio_id' => $baja->id]);
                    }
                    $p['baja_temporal_programada'] = false;
                    $factura->payload = $p;
                    $factura->saveQuietly();
                });
            });
    }
}
