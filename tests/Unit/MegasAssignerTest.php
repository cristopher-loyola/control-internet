<?php

namespace Tests\Unit;

use App\Services\MegasAssigner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias para el sistema de asignación de megas.
 *
 * Verifica:
 * - Todas las combinaciones válidas de costo/tecnología
 * - Manejo de errores por costo/tecnología inválidos
 * - Tolerancia a distintos formatos de costo (300, "300.00")
 */
class MegasAssignerTest extends TestCase
{
    /** @dataProvider matrixProvider */
    public function test_assign_returns_expected_megas($costo, $tec, $expected)
    {
        $this->assertSame($expected, MegasAssigner::assign($costo, $tec));
    }

    public static function matrixProvider(): array
    {
        return [
            // Costo 300
            [300, 'FOD', 30], [300, 'FOI', 20], [300, 'INA', 10],
            // Costo 400
            [400, 'FOD', 50], [400, 'FOI', 30], [400, 'INA', 20],
            // Costo 500
            [500, 'FOD', 70], [500, 'FOI', 40], [500, 'INA', 30],
            // Costo 600
            [600, 'FOD', 100], [600, 'FOI', 50], [600, 'INA', 40],
            // Formatos alternos de costo
            ['300.00', 'FOD', 30],
            [400.0, 'FOI', 30],
            ['600.00', 'ina', 40], // tecnología en minúsculas
        ];
    }

    public function test_assign_throws_on_invalid_cost()
    {
        $this->expectException(InvalidArgumentException::class);
        MegasAssigner::assign(350, 'FOD');
    }

    public function test_assign_throws_on_invalid_technology()
    {
        $this->expectException(InvalidArgumentException::class);
        MegasAssigner::assign(300, 'XYZ');
    }
}
