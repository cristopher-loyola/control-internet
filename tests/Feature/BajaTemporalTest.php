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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_baja_programada_respeta_adelanto_y_activa_al_mes_siguiente(): void
    {
        foreach ([['admin', 'admin.pagos.facturas.store', 1, false], ['pagos', 'pagos.recibos.facturas.store', 3, true]] as [$role, $route, $months, $nextMonth]) {
            Carbon::setTestNow(Carbon::create(2026, 10, 31, 10));
            [$estado, $pagado, $servicio] = $this->seedCatalogs();
            $numero = $role === 'admin' ? 1111 : 1112;
            $usuario = Usuario::create([
                'numero_servicio' => $numero, 'nombre_cliente' => 'Cliente adelantado',
                'domicilio' => '-', 'estado_id' => $estado, 'estatus_servicio_id' => $pagado,
                'servicio_id' => $servicio, 'tarifa' => 300, 'adeudo_monto' => 0,
            ]);
            $adelanto = \App\Models\Factura::create([
                'reference_number' => $numero, 'numero_servicio' => (string) $numero,
                'periodo' => '2026-10', 'total' => 300 * $months,
                'payload' => ['prepay' => 'si', 'prepay_months' => $months, 'prepay_next_month' => $nextMonth],
            ]);
            $inicio = $nextMonth ? '2027-02-01' : '2026-11-01';
            $fin = $nextMonth ? '2027-04-01' : '2027-01-01';
            $this->actingAs(User::factory()->create(['role' => $role]));
            $response = $this->postJson(route($route), [
                'numero_servicio' => (string) $numero, 'total' => 120,
                'payload' => ['otro' => 'baja_temporal', 'baja_temporal_months' => 2, 'mensualidad' => 300,
                    'baja_temporal_desde' => '2000-01-01'],
            ])->assertOk()->assertJson(['ok' => true]);
            $factura = \App\Models\Factura::findOrFail($response->json('id'));
            $this->assertEquals(120, $factura->total);
            $this->assertSame($inicio, $factura->payload['baja_temporal_desde']);
            $this->assertSame($fin, $factura->payload['baja_temporal_hasta']);
            $this->assertEquals($pagado, $usuario->fresh()->estatus_servicio_id);
            $this->assertSame(substr($fin, 0, 7), $usuario->fresh()->proximo_pago);
            $this->get(route($role.'.dashboard.baja-temporal'))->assertOk()->assertSee($inicio)->assertSee($fin);
            app(\App\Services\BajaTemporalService::class)->activarProgramadas();
            $this->assertEquals($pagado, $usuario->fresh()->estatus_servicio_id);
            Carbon::setTestNow(Carbon::parse($inicio));
            $this->artisan('usuarios:activar-bajas-temporales')->assertSuccessful();
            $this->assertSame('Baja temporal', $usuario->fresh()->estatusServicio->nombre);
            $this->assertFalse($factura->fresh()->payload['baja_temporal_programada']);
            $adeudo = app(\App\Services\MorosidadService::class)->calcularAdeudoUsuario((string) $numero, null);
            $this->assertEquals(0, $adeudo['pendiente']);
            Carbon::setTestNow(Carbon::parse($fin)->subDay());
            $adeudo = app(\App\Services\MorosidadService::class)->calcularAdeudoUsuario((string) $numero, null);
            $this->assertEquals(0, $adeudo['pendiente']);
            Carbon::setTestNow(Carbon::parse($fin));
            $adeudo = app(\App\Services\MorosidadService::class)->calcularAdeudoUsuario((string) $numero, null);
            $this->assertEquals(300, $adeudo['pendiente']);
            $this->assertSame('si', $adelanto->fresh()->payload['prepay']);
        }
    }

    public function test_inicio_ignora_adelantos_cancelados_y_vencidos(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 10));
        $factura = \App\Models\Factura::create([
            'reference_number' => 999, 'numero_servicio' => '1111', 'periodo' => '2026-10',
            'total' => 900, 'payload' => ['prepay' => 'si', 'prepay_months' => 3],
        ]);
        $factura->delete();
        $service = app(\App\Services\BajaTemporalService::class);
        $this->assertSame('2026-10-01', $service->inicio('1111')->toDateString());
        $factura->restore();
        Carbon::setTestNow(Carbon::create(2027, 1, 31, 10));
        $this->assertSame('2027-01-01', $service->inicio('1111')->toDateString());
    }

    public function test_pago_durante_baja_reanuda_mensualidad_y_proximo_mes(): void
    {
        foreach (['admin' => 'admin.pagos', 'pagos' => 'pagos.recibos'] as $role => $prefix) {
            Carbon::setTestNow(Carbon::create(2026, 12, 1, 10));
            [$estado, $pagado, $servicio] = $this->seedCatalogs();
            $numero = $role === 'admin' ? '1121' : '1122';
            $usuario = Usuario::create([
                'numero_servicio' => $numero, 'nombre_cliente' => 'Reanudacion', 'domicilio' => '-',
                'estado_id' => $estado, 'estatus_servicio_id' => $pagado,
                'servicio_id' => $servicio, 'tarifa' => 300, 'adeudo_monto' => 0,
            ]);
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->postJson(route($prefix.'.facturas.store'), [
                'numero_servicio' => $numero, 'total' => 180,
                'payload' => ['otro' => 'baja_temporal', 'baja_temporal_months' => 3, 'mensualidad' => 300],
            ])->assertOk();
            Carbon::setTestNow(Carbon::create(2027, 1, 31, 10));
            $this->getJson(route($prefix.'.deuda', ['numero' => $numero]))
                ->assertOk()->assertJson(['puede_reanudar_baja' => true, 'mensualidad_reanudacion' => 300]);
            $this->assertSame('2027-03', $usuario->fresh()->proximo_pago);
            $response = $this->postJson(route($prefix.'.facturas.store'), [
                'numero_servicio' => $numero, 'total' => 300,
                'payload' => ['otro' => 'no', 'mensualidad' => 300, 'recargo' => 'no', 'mes_siguiente' => true],
            ])->assertOk();
            $factura = \App\Models\Factura::findOrFail($response->json('id'));
            $this->assertEquals(300, $factura->total);
            $this->assertSame('2027-01', $factura->periodo);
            $this->assertTrue($factura->payload['reanuda_baja_temporal']);
            $this->assertSame('2027-03', $factura->payload['proximo_pago_previo']);
            $this->assertSame('2027-02', $usuario->fresh()->proximo_pago);
            $this->assertSame('Pagado', $usuario->fresh()->estatusServicio->nombre);
            $this->getJson(route($prefix.'.deuda', ['numero' => $numero]))->assertJson(['puede_reanudar_baja' => false]);
            app(\App\Services\BajaTemporalService::class)->activarProgramadas();
            $this->assertSame('Pagado', $usuario->fresh()->estatusServicio->nombre);
            Carbon::setTestNow(Carbon::create(2027, 2, 1, 10));
            $deuda = app(\App\Services\MorosidadService::class)->calcularAdeudoUsuario($numero);
            $this->assertEquals(300, $deuda['pendiente']);
            $this->postJson(route($prefix.'.facturas.cancel', $factura->id), ['motivo' => 'Pago registrado por error']);
            $this->assertSoftDeleted('facturas', ['id' => $factura->id]);
            $this->assertSame('Baja temporal', $usuario->fresh()->estatusServicio->nombre);
            $this->assertSame('2027-03', $usuario->fresh()->proximo_pago);
            $this->postJson(route($prefix.'.facturas.store'), [
                'numero_servicio' => $numero, 'total' => 0,
                'payload' => ['otro' => 'no', 'mensualidad' => 300, 'manual_total_enabled' => true,
                    'manual_total_value' => 0, 'manual_total_reason' => 'Intento sin cobro'],
            ])->assertStatus(422);
            $this->assertSame('Baja temporal', $usuario->fresh()->estatusServicio->nombre);
            $this->assertSame('2027-03', $usuario->fresh()->proximo_pago);
        }
    }
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
        DB::table('estatus_servicios')->insert([
            'nombre' => 'Pendiente',
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

    private function ensureBajaTemporalStatus(): int
    {
        $now = now();

        $id = DB::table('estatus_servicios')->whereRaw('LOWER(nombre) = ?', ['baja temporal'])->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('estatus_servicios')->insertGetId([
            'nombre' => 'Baja temporal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_admin_baja_temporal_con_adeudos_suma_costo_a_adeudo_y_registra_baja_temporal(): void
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
            'total' => 410,
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

        $resp->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseHas('facturas', [
            'numero_servicio' => '9001',
            'total' => 410.00,
        ]);

        $u = Usuario::where('numero_servicio', 9001)->first();
        $this->assertNotNull($u);
        $this->assertEquals(0.0, (float) $u->adeudo_monto);

        $estatusNombre = DB::table('estatus_servicios')->where('id', $u->estatus_servicio_id)->value('nombre');
        $this->assertEquals('Baja temporal', $estatusNombre);
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

    public function test_admin_dashboard_baja_temporal_muestra_clientes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusPagadoId, $servicioId] = $this->seedCatalogs();
        $bajaId = $this->ensureBajaTemporalStatus();

        Usuario::create([
            'numero_servicio' => 9010,
            'nombre_cliente' => 'Cliente BT',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $bajaId,
            'servicio_id' => $servicioId,
            'tarifa' => 300,
            'adeudo_descripcion' => null,
            'adeudo_monto' => 0,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard.baja-temporal'))
            ->assertOk()
            ->assertSee('Clientes en baja temporal')
            ->assertSee('9010');
    }

    public function test_pagos_dashboard_baja_temporal_muestra_clientes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusPagadoId, $servicioId] = $this->seedCatalogs();
        $bajaId = $this->ensureBajaTemporalStatus();

        Usuario::create([
            'numero_servicio' => 9011,
            'nombre_cliente' => 'Cliente BT 2',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $bajaId,
            'servicio_id' => $servicioId,
            'tarifa' => 300,
            'adeudo_descripcion' => null,
            'adeudo_monto' => 0,
        ]);

        $pagos = User::factory()->create(['role' => 'pagos']);
        $this->actingAs($pagos);

        $this->get(route('pagos.dashboard.baja-temporal'))
            ->assertOk()
            ->assertSee('Clientes en baja temporal')
            ->assertSee('9011');
    }

    public function test_baja_temporal_muestra_fecha_hasta_según_ultima_factura(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusPagadoId, $servicioId] = $this->seedCatalogs();
        $bajaId = $this->ensureBajaTemporalStatus();

        Usuario::create([
            'numero_servicio' => 9050,
            'nombre_cliente' => 'Cliente BT Fecha',
            'domicilio' => '-',
            'estado_id' => $estadoId,
            'estatus_servicio_id' => $bajaId,
            'servicio_id' => $servicioId,
            'tarifa' => 300,
            'adeudo_descripcion' => null,
            'adeudo_monto' => 0,
        ]);

        \App\Models\Factura::create([
            'reference_number' => 500,
            'numero_servicio' => '9050',
            'periodo' => null,
            'total' => 60.00,
            'payload' => [
                'otro' => 'baja_temporal',
                'baja_temporal_months' => 2,
            ],
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard.baja-temporal'))
            ->assertOk()
            ->assertSee('9050')
            ->assertSee('2026-06-01');
    }

    public function test_pagos_requiere_motivo_si_se_edita_total(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusId, $servicioId] = $this->seedCatalogs();

        Usuario::create([
            'numero_servicio' => 9020,
            'nombre_cliente' => 'Cliente 20',
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

        $this->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => '9020',
            'usuario_id' => null,
            'total' => 123.45,
            'payload' => [
                'nombre' => 'Cliente 20',
                'mensualidad' => 300,
                'recargo' => 'no',
                'pago_anterior' => 0,
                'metodo' => 'Efectivo',
                'cobro' => 'Ivan',
                'otro' => 'no',
                'manual_total_enabled' => true,
                'manual_total_value' => 123.45,
                'manual_total_reason' => '',
            ],
        ])->assertStatus(422);
    }

    public function test_pagos_guarda_total_editado_y_audita_override(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 10, 0, 0));
        [$estadoId, $estatusId, $servicioId] = $this->seedCatalogs();

        Usuario::create([
            'numero_servicio' => 9021,
            'nombre_cliente' => 'Cliente 21',
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

        $resp = $this->postJson(route('pagos.recibos.facturas.store'), [
            'numero_servicio' => '9021',
            'usuario_id' => null,
            'total' => 111.11,
            'payload' => [
                'nombre' => 'Cliente 21',
                'mensualidad' => 300,
                'recargo' => 'no',
                'pago_anterior' => 0,
                'metodo' => 'Efectivo',
                'cobro' => 'Ivan',
                'otro' => 'no',
                'manual_total_enabled' => true,
                'manual_total_value' => 111.11,
                'manual_total_reason' => 'Ajuste autorizado',
            ],
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('facturas', [
            'numero_servicio' => '9021',
            'total' => 111.11,
        ]);

        $facturaId = $resp->json('id');
        $this->assertNotEmpty($facturaId);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'factura_total_override',
            'table_name' => 'facturas',
            'entity_id' => (string) $facturaId,
        ]);
    }
}
