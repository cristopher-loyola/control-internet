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

    public function test_consolidado_con_adelanto_suma_adeudo_mas_meses_sin_descuento()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 10, 12, 0, 0, 'UTC'));
        $u = Usuario::create([
            'numero_servicio' => '2100',
            'nombre_cliente' => 'Cliente Adelanto',
            'tarifa' => '300',
            'domicilio' => 'Av 123',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Factura::create([
            'reference_number' => 21,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-03',
            'total' => 300,
            'payload' => [],
        ]);
        $svc = new MorosidadService();
        $adeudo = $svc->calcularAdeudoUsuario($u->numero_servicio, '2026-07');
        $this->assertTrue($adeudo['ok']);
        $this->assertEquals(1250.0, $adeudo['pendiente']);
        $mensualidad = 300.0;
        $meses = 3;
        $adelanto = $mensualidad * $meses;
        $totalConsolidado = $adeudo['pendiente'] + $adelanto;
        $this->assertEquals(2150.0, $totalConsolidado);
    }

    public function test_consolidado_con_adelanto_seis_meses_con_descuento()
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 10, 12, 0, 0, 'UTC'));
        $u = Usuario::create([
            'numero_servicio' => '2200',
            'nombre_cliente' => 'Cliente Descuento',
            'tarifa' => '300',
            'domicilio' => 'Av 456',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Factura::create([
            'reference_number' => 22,
            'numero_servicio' => $u->numero_servicio,
            'periodo' => '2026-03',
            'total' => 300,
            'payload' => [],
        ]);
        $svc = new MorosidadService();
        $adeudo = $svc->calcularAdeudoUsuario($u->numero_servicio, '2026-07');
        $this->assertTrue($adeudo['ok']);
        $this->assertEquals(1250.0, $adeudo['pendiente']);
        $matrix = [
            6  => ['percent' => 10, 'totals' => [300=>1620, 400=>2160, 500=>2700, 600=>3240]],
            7  => ['percent' => 11, 'totals' => [300=>1869, 400=>2492, 500=>3115, 600=>3738]],
            8  => ['percent' => 12, 'totals' => [300=>2112, 400=>2816, 500=>3520, 600=>4224]],
            9  => ['percent' => 13, 'totals' => [300=>2349, 400=>3132, 500=>3915, 600=>4698]],
            10 => ['percent' => 14, 'totals' => [300=>2580, 400=>3440, 500=>4300, 600=>5160]],
            11 => ['percent' => 15, 'totals' => [300=>2805, 400=>3740, 500=>4675, 600=>5610]],
            12 => ['percent' => 16, 'totals' => [300=>3024, 400=>4032, 500=>5040, 600=>6048]],
        ];
        $mensualidad = 300.0;
        $meses = 6;
        $info = $matrix[$meses];
        $adelanto = $info['totals'][$mensualidad] ?? round($mensualidad * $meses * (1 - ($info['percent']/100)), 2);
        $totalConsolidado = $adeudo['pendiente'] + $adelanto;
        $this->assertEquals(2870.0, $totalConsolidado);
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
