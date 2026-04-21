<?php

namespace App\Services;

use App\Models\CorteCaja;
use App\Models\Factura;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LocationPaymentsService
{
    /**
     * Obtiene las métricas de pagos para una ubicación específica
     */
    public static function getLocationPaymentMetrics(string $location, Carbon $from, Carbon $to): array
    {
        $ventas = Factura::whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('cajero', function ($q) use ($location) {
                $q->where('role', $location);
            })
            ->get(['total', 'payload']);

        $total = (float) $ventas->sum('total');
        $count = $ventas->count();
        $promedio = $count > 0 ? round($total / $count, 2) : 0;

        return [
            'pagos' => round($total, 2),
            'count' => $count,
            'promedio' => $promedio,
        ];
    }

    /**
     * Obtiene la información del último corte para una ubicación específica
     */
    public static function getLastCorteInfo(string $location): array
    {
        // No usar caché para datos de cortes, ya que cambian dinámicamente
        $ultimoCorte = CorteCaja::where('zona', $location)
            ->where('estado', 'activo')
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$ultimoCorte) {
            return [
                'fecha_corte' => null,
                'total_recaudado' => 0,
                'total_pagos' => 0,
            ];
        }

        // Obtener facturas desde el inicio del corte hasta ahora (sin caché)
        $facturasDesdeCorte = Factura::whereNull('deleted_at')
            ->where('created_at', '>=', $ultimoCorte->fecha_inicio)
            ->whereHas('cajero', function ($q) use ($location) {
                $q->where('role', $location);
            })
            ->get(['total']);

        $totalRecaudado = (float) $facturasDesdeCorte->sum('total');
        $totalPagos = $facturasDesdeCorte->count();

        return [
            'fecha_corte' => $ultimoCorte->fecha_inicio->format('d/m/Y H:i'),
            'total_recaudado' => round($totalRecaudado, 2),
            'total_pagos' => $totalPagos,
        ];
    }

    /**
     * Obtiene todas las métricas de ubicaciones
     */
    public static function getAllLocationMetrics(Carbon $from, Carbon $to): array
    {
        $locations = ['rosalito', 'chivato', 'pozo_hondo'];
        $metrics = [];

        foreach ($locations as $location) {
            $metrics[$location] = self::getLocationPaymentMetrics($location, $from, $to);
        }

        return $metrics;
    }

    /**
     * Agrega las métricas de ubicaciones al array de datos existente
     */
    public static function addLocationMetricsToArray(array $data, Carbon $from, Carbon $to): array
    {
        $locationMetrics = self::getAllLocationMetrics($from, $to);

        // Mapear a los nombres esperados por los componentes
        $data['rosalito_pagos'] = $locationMetrics['rosalito']['pagos'];
        $data['rosalito_count'] = $locationMetrics['rosalito']['count'];
        $data['rosalito_promedio'] = $locationMetrics['rosalito']['promedio'];

        $data['perfil_chivato_pagos'] = $locationMetrics['chivato']['pagos'];
        $data['perfil_chivato_count'] = $locationMetrics['chivato']['count'];
        $data['perfil_chivato_promedio'] = $locationMetrics['chivato']['promedio'];

        $data['pozo_hondo_pagos'] = $locationMetrics['pozo_hondo']['pagos'];
        $data['pozo_hondo_count'] = $locationMetrics['pozo_hondo']['count'];
        $data['pozo_hondo_promedio'] = $locationMetrics['pozo_hondo']['promedio'];

        // Obtener información del último corte para cada ubicación
        $rosalitoCorte = self::getLastCorteInfo('rosalito');
        $chivatoCorte = self::getLastCorteInfo('chivato');
        $pozoHondoCorte = self::getLastCorteInfo('pozo_hondo');

        // Agregar información de cortes para cada ubicación
        $data['rosalito_fecha_corte'] = $rosalitoCorte['fecha_corte'];
        $data['rosalito_recaudado_corte'] = $rosalitoCorte['total_recaudado'];
        $data['rosalito_pagos_corte'] = $rosalitoCorte['total_pagos'];

        $data['perfil_chivato_fecha_corte'] = $chivatoCorte['fecha_corte'];
        $data['perfil_chivato_recaudado_corte'] = $chivatoCorte['total_recaudado'];
        $data['perfil_chivato_pagos_corte'] = $chivatoCorte['total_pagos'];

        $data['pozo_hondo_fecha_corte'] = $pozoHondoCorte['fecha_corte'];
        $data['pozo_hondo_recaudado_corte'] = $pozoHondoCorte['total_recaudado'];
        $data['pozo_hondo_pagos_corte'] = $pozoHondoCorte['total_pagos'];

        return $data;
    }
}
