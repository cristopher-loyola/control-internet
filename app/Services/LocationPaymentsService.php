<?php

namespace App\Services;

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

        return $data;
    }
}
