<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelacionServicioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Estado::create(['id' => 1, 'nombre' => 'Activado']);
        Estado::create(['id' => 2, 'nombre' => 'Desactivado']);

        EstatusServicio::create(['id' => 1, 'nombre' => 'Pagado']);
        EstatusServicio::create(['id' => 2, 'nombre' => 'Suspendido']);
        EstatusServicio::create(['id' => 3, 'nombre' => 'Cancelado']);
        EstatusServicio::create(['id' => 4, 'nombre' => 'Pendiente de pago']);

        Servicio::create(['id' => 1, 'nombre' => 'Internet Residencial']);
    }

    public function test_pagos_cancelacion_marca_usuario_como_cancelado(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $cliente = Usuario::create([
            'numero_servicio' => '9001',
            'nombre_cliente' => 'Cliente Cancelación',
            'tarifa' => '400',
            'domicilio' => 'Calle 1',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);

        $res = $this->actingAs($user)->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => $cliente->numero_servicio,
            'total' => 0,
            'payload' => [
                'nombre' => $cliente->nombre_cliente,
                'metodo' => 'Efectivo',
                'otro' => 'cancelacion',
            ],
        ]);

        $res->assertOk();
        $res->assertJsonPath('ok', true);

        $cliente->refresh();
        $this->assertSame(3, (int) $cliente->estatus_servicio_id);
        $this->assertSame(2, (int) $cliente->estado_id);
    }

    public function test_admin_cancelacion_marca_usuario_como_cancelado(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $cliente = Usuario::create([
            'numero_servicio' => '9002',
            'nombre_cliente' => 'Cliente Cancelación Admin',
            'tarifa' => '400',
            'domicilio' => 'Calle 2',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);

        $res = $this->actingAs($user)->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => $cliente->numero_servicio,
            'total' => 0,
            'payload' => [
                'nombre' => $cliente->nombre_cliente,
                'metodo' => 'Efectivo',
                'otro' => 'cancelacion',
            ],
        ]);

        $res->assertOk();
        $res->assertJsonPath('ok', true);

        $cliente->refresh();
        $this->assertSame(3, (int) $cliente->estatus_servicio_id);
        $this->assertSame(2, (int) $cliente->estado_id);
    }
}
