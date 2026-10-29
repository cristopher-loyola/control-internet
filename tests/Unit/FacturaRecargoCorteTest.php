<?php

namespace Tests\Unit;

use App\Models\Factura;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FacturaRecargoCorteTest extends TestCase
{
    public static function pagos(): array
    {
        return [
            'mensual sin recargo' => [300, ['recargo' => 'no'], 0],
            'mensual con recargo' => [350, ['recargo' => 'si'], 50],
            'mensual conserva su regla anterior' => [300, ['recargo' => 'si'], 50],
            'adelanto con marca errónea de recargo' => [300, ['prepay' => 'si', 'prepay_total' => 300, 'recargo' => 'si', 'adeudo_pendiente' => 0], 0],
            'adelanto con recargo cobrado' => [350, ['prepay' => 'si', 'prepay_total' => 300, 'recargo' => 'si', 'adeudo_pendiente' => 0], 50],
            'adelanto con descuento y recargo' => [340, ['prepay' => 'si', 'prepay_total' => 300, 'recargo' => 'si', 'descuento' => 10], 50],
            'adelanto con descuento sin recargo real' => [290, ['prepay' => 'si', 'prepay_total' => 300, 'recargo' => 'si', 'descuento' => 10], 0],
            'adelanto y adeudo con recargo' => [650, ['prepay' => 'si', 'prepay_total' => 300, 'recargo' => 'si', 'adeudo_pendiente' => 350], 50],
        ];
    }

    #[DataProvider('pagos')]
    public function test_descuenta_solo_el_recargo_cobrado_sin_cambiar_pagos_normales(float $total, array $payload, float $esperado): void
    {
        $factura = new Factura(['total' => $total, 'payload' => $payload]);
        $this->assertSame($esperado, $factura->recargoParaCorte());
        $this->assertSame($total, (float) $factura->total);
        $this->assertSame($payload, $factura->payload);
    }
}
