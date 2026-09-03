<?php

namespace Tests\Unit;

use App\Models\Usuario;
use App\Services\MorosidadService;
use PHPUnit\Framework\TestCase;

class MorosidadCorteRuleTest extends TestCase
{
    public function test_adeudo_del_mes_anterior_entra_a_corte_desde_el_primer_dia(): void
    {
        $usuario = new Usuario(['adeudo_monto' => 0]);
        $adeudo = ['meses_adeudo' => 1, 'desde_periodo' => '2026-08'];

        $this->assertTrue(
            (new MorosidadService)->debeSerCortado($usuario, $adeudo, '2026-09', 1)
        );
    }

    public function test_adeudo_solamente_del_mes_actual_no_entra_a_corte(): void
    {
        $usuario = new Usuario(['adeudo_monto' => 0]);
        $adeudo = ['meses_adeudo' => 1, 'desde_periodo' => '2026-09'];

        $this->assertFalse(
            (new MorosidadService)->debeSerCortado($usuario, $adeudo, '2026-09', 30)
        );
    }
}
