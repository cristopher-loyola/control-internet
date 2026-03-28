<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Sistema de asignación automática de megas según costo y tecnología.
 *
 * Ejemplos de uso:
 *
 *   $megas = MegasAssigner::assign(400, 'FOI'); // 30
 *   $megas = MegasAssigner::assign(600, 'ina'); // 40
 *
 * Reglas:
 * - Costos válidos: 300, 400, 500, 600
 * - Tecnologías válidas: FOD, FOI, INA (no sensible a mayúsculas)
 * - Retorna un entero con los megas asignados
 * - Lanza InvalidArgumentException si los parámetros son inválidos
 */
class MegasAssigner
{
    /**
     * Asigna megas en función del costo del paquete y la tecnología.
     *
     * @param  int|float|string  $costo  Costo del paquete (300, 400, 500, 600). Se aceptan "300.00" o 300.0.
     * @param  string  $tecnologia  Tipo de tecnología (FOD, FOI, INA), sin distinción de mayúsculas/minúsculas.
     * @return int Megas asignados (entero).
     *
     * @throws InvalidArgumentException Si el costo o la tecnología no son válidos.
     */
    public static function assign($costo, string $tecnologia): int
    {
        // Normalizar costo: admitir strings/decimales como "300.00" → 300
        if (is_string($costo)) {
            $costo = trim($costo);
            // Convertir "300.00" → 300
            if (is_numeric($costo)) {
                $costo = (int) round((float) $costo);
            }
        } elseif (is_float($costo)) {
            $costo = (int) round($costo);
        }

        $validCosts = [300, 400, 500, 600];
        if (! in_array($costo, $validCosts, true)) {
            throw new InvalidArgumentException('Costo de paquete inválido. Debe ser uno de: 300, 400, 500, 600.');
        }

        // Normalizar tecnología
        $tec = strtoupper(trim($tecnologia));
        $validTechs = ['FOD', 'FOI', 'INA'];
        if (! in_array($tec, $validTechs, true)) {
            throw new InvalidArgumentException('Tecnología inválida. Debe ser una de: FOD, FOI, INA.');
        }

        // Matriz de asignación
        $matrix = [
            300 => ['FOD' => 30, 'FOI' => 20, 'INA' => 10],
            400 => ['FOD' => 50, 'FOI' => 30, 'INA' => 20],
            500 => ['FOD' => 70, 'FOI' => 40, 'INA' => 30],
            600 => ['FOD' => 100, 'FOI' => 50, 'INA' => 40],
        ];

        return $matrix[$costo][$tec];
    }
}
