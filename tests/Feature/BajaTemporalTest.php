<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BajaTemporalTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogs(): array
    {
        $now = now();

        $estadoId = DB::table('estados')->insertGetId([
            'nombre' => 'Activo',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $estatusId = DB::table('estatus_servicios')->insertGetId([
            'nombre' => 'Pagado',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $servicioId = DB::table('servicios')->insertGetId([
            'nombre' => 'Internet',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$estadoId, $estatusId, $servicioId];
    }

    public function test_admin_baja_temporal_rechaza_si_tiene_adeudos(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusId, $servicioId] = $this->seedCatalogs();

        Usuario::create([
            'numero_servicio' => 9001,
            'nombre_cliente' => 'Cliente',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $estatusId,
            'servicio_id' => $servicioId,
            'tarifa' => 300,
            'adeudo_descripcion' => 'Adeuda marzo',
            'adeudo_monto' => 50,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $resp = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '9001',
            'usuario_id' => null,
            'total' => 60,
            'payload' => [
                'nombre' => 'Cliente',
                'mensualidad' => 300,
                'recargo' => 'no',
                'pago_anterior' => 0,
                'otro' => 'baja_temporal',
                'baja_temporal_months' => 1,
                'descuento' => 0,
            ],
        ]);

        $resp->assertStatus(409)->assertJson(['ok' => false]);
        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_pagos_baja_temporal_calcula_total_20_por_ciento_por_mes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusId, $servicioId] = $this->seedCatalogs();

        Usuario::create([
            'numero_servicio' => 9002,
            'nombre_cliente' => 'Cliente 2',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $estatusId,
            'servicio_id' => $servicioId,
            'tarifa' => 0,
            'adeudo_descripcion' => null,
            'adeudo_monto' => 0,
        ]);

        $pagos = User::factory()->create(['role' => 'pagos']);
        $this->actingAs($pagos);

        $resp = $this->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => '9002',
            'usuario_id' => null,
            'total' => 999,
            'payload' => [
                'nombre' => 'Cliente 2',
                'mensualidad' => 300,
                'recargo' => 'no',
                'pago_anterior' => 0,
                'otro' => 'baja_temporal',
                'baja_temporal_months' => 3,
                'descuento' => 0,
            ],
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseHas('facturas', [
            'numero_servicio' => '9002',
            'total' => 180.00,
        ]);

        $u = Usuario::where('numero_servicio', 9002)->first();
        $this->assertNotNull($u);
        $estatusNombre = DB::table('estatus_servicios')->where('id', $u->estatus_servicio_id)->value('nombre');
        $this->assertEquals('Baja temporal', $estatusNombre);
    }

    public function test_pagos_baja_temporal_genera_nuevo_folio_separado_del_pago_mensual(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusId, $servicioId] = $this->seedCatalogs();

        Usuario::create([
            'numero_servicio' => 9003,
            'nombre_cliente' => 'Cliente 3',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $estatusId,
            'servicio_id' => $servicioId,
            'tarifa' => 300,
            'adeudo_descripcion' => null,
            'adeudo_monto' => 0,
        ]);

        $pagos = User::factory()->create(['role' => 'pagos']);
        $this->actingAs($pagos);

        $mensualPayload = [
            'nombre' => 'Cliente 3',
            'mensualidad' => 300,
            'recargo' => 'no',
            'pago_anterior' => 0,
            'otro' => 'no',
            'descuento' => 0,
        ];

        $mensual = $this->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => '9003',
            'usuario_id' => null,
            'total' => 999,
            'payload' => $mensualPayload,
        ])->assertOk()->json();

        $bajaPayload = [
            'nombre' => 'Cliente 3',
            'mensualidad' => 300,
            'recargo' => 'no',
            'pago_anterior' => 0,
            'otro' => 'baja_temporal',
            'baja_temporal_months' => 2,
            'descuento' => 0,
        ];

        $baja = $this->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => '9003',
            'usuario_id' => null,
            'total' => 999,
            'payload' => $bajaPayload,
        ])->assertOk()->json();

        $this->assertNotEquals($mensual['referencia'], $baja['referencia']);
        $this->assertDatabaseCount('facturas', 2);
    }
}
