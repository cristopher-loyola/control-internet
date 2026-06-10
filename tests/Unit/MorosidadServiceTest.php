<?php

namespace Tests\Unit;

use App\Models\Usuario;
use App\Models\Factura;
use App\Services\MorosidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MorosidadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adeudo_un_mes_con_recargo()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 12, 0, 0, 'UTC'));
        $u = Usuario::create([
            'numero_servicio' => '1001',
            'nombre_cliente' => 'Cliente Uno',
            'tarifa' => '400',
            'domicilio' => 'Calle 123',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Factura::create([
            'reference_number' => 1,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-02',
            'total' => 400,
            'payload' => [],
        ]);
        $svc = new MorosidadService();
        $res = $svc->calcularAdeudoUsuario($u->numero_servicio, '2026-03');
        $this->assertTrue($res['ok']);
        $this->assertSame('2026-03', $res['desde_periodo']);
        $this->assertSame(1, $res['meses_adeudo']);
        $this->assertEquals(50.0, $res['recargo']);
        $this->assertEquals(450.0, $res['pendiente']);
    }

    public function test_adeudo_varios_meses_con_recargo()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 10, 12, 0, 0, 'UTC'));
        $u = Usuario::create([
            'numero_servicio' => '2002',
            'nombre_cliente' => 'Cliente Dos',
            'tarifa' => '300',
            'domicilio' => 'Avenida 456',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Factura::create([
            'reference_number' => 2,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-03',
            'total' => 300,
            'payload' => [],
        ]);
        $svc = new MorosidadService();
        $res = $svc->calcularAdeudoUsuario($u->numero_servicio, '2026-07');
        $this->assertTrue($res['ok']);
        $this->assertSame('2026-04', $res['desde_periodo']);
        $this->assertSame(4, $res['meses_adeudo']);
        $this->assertEquals(50.0, $res['recargo']);
        $this->assertEquals(1250.0, $res['pendiente']);
    }

    public function test_adeudo_con_pagos_parciales_se_resta_del_pendiente()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0, 'UTC'));
        $u = Usuario::create([
            'numero_servicio' => '3003',
            'nombre_cliente' => 'Cliente Tres',
            'tarifa' => '500',
            'domicilio' => 'Callejón 789',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Factura::create([
            'reference_number' => 3,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-03',
            'total' => 500,
            'payload' => [],
        ]);
        Factura::create([
            'reference_number' => 4,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-04',
            'total' => 200,
            'payload' => [],
        ]);
        $svc = new MorosidadService();
        $res = $svc->calcularAdeudoUsuario($u->numero_servicio, '2026-05');
        $this->assertTrue($res['ok']);
        $this->assertSame('2026-04', $res['desde_periodo']);
        $this->assertSame(2, $res['meses_adeudo']);
        $this->assertEquals(50.0, $res['recargo']);
        $this->assertEquals(850.0, $res['pendiente']);
    }
}

