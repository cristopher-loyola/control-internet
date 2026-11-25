<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\Factura;
use App\Models\User;
use App\Models\Usuario;
use App\Services\WhatsAppNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerfilMesasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        DB::table('estados')->updateOrInsert(['id' => 1], ['nombre' => 'Activado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Pagado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 4], ['nombre' => 'Pendiente de pago']);
        DB::table('servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Internet']);
        $this->mock(WhatsAppNotifierService::class)
            ->shouldReceive('enviarNotificacionReactivacion')->andReturn(false);
    }

    private function cliente(): Usuario
    {
        return Usuario::create([
            'numero_servicio' => '8500', 'nombre_cliente' => 'Cliente mesas',
            'domicilio' => 'Domicilio de prueba', 'tarifa' => 300,
            'estado_id' => 1, 'estatus_servicio_id' => 4, 'servicio_id' => 1,
            'proximo_pago' => '2026-11',
        ]);
    }

    private function pagoConRecargo(): array
    {
        return [
            'numero_servicio' => '8500', 'total' => 350,
            'payload' => [
                'nombre' => 'Cliente mesas', 'mensualidad' => 300,
                'recargo' => 'si', 'metodo' => 'Efectivo', 'otro' => 'no',
                'prepay' => 'no', 'cobro' => 'Mesas',
            ],
        ];
    }

    public function test_login_redirige_al_panel_de_mesas_y_muestra_todas_sus_secciones(): void
    {
        $cajero = User::factory()->create(['role' => 'mesas', 'password' => bcrypt('secreto123')]);

        $this->post('/login', ['email' => $cajero->email, 'password' => 'secreto123'])
            ->assertRedirect(route('mesas.index', absolute: false));

        foreach (['mesas.index', 'mesas.pagos', 'mesas.corte', 'mesas.historial', 'mesas.historial-cortes'] as $ruta) {
            $this->get(route($ruta))->assertOk()->assertSee('Mesas');
        }
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('mesas.index'))
            ->assertSee(route('mesas.corte.iniciar'), false)
            ->assertSee(route('mesas.corte.finalizar'), false);

        // Exportar Excel aparece solo con corte activo, igual que en los demás perfiles de zona.
        $this->postJson(route('mesas.corte.iniciar'))->assertOk();
        $this->get(route('mesas.index'))->assertSee(route('mesas.corte.exportar'), false);
    }

    public function test_perfiles_no_pueden_entrar_al_otro(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'pozo_hondo']));
        $this->get(route('mesas.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'mesas']));
        $this->get(route('pozo_hondo.index'))->assertForbidden();
    }

    public function test_no_cobra_sin_corte_activo(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'mesas']));
        $this->cliente();

        $this->postJson(route('mesas.recibos.facturas.store'), $this->pagoConRecargo())
            ->assertStatus(422);
        $this->assertSame(0, Factura::count());
    }

    public function test_cobra_cliente_registrado_y_el_corte_separa_comision_sin_recargo(): void
    {
        $cajero = User::factory()->create(['role' => 'mesas']);
        $this->actingAs($cajero);
        $usuario = $this->cliente();

        $this->getJson(route('mesas.recibos.lookup', ['numero' => '8500']))
            ->assertOk()->assertJsonPath('data.nombre_cliente', 'Cliente mesas');

        $this->postJson(route('mesas.corte.iniciar'))->assertOk();
        $corte = CorteCaja::where('zona', 'mesas')->where('user_id', $cajero->id)->firstOrFail();

        $id = $this->postJson(route('mesas.recibos.facturas.store'), $this->pagoConRecargo())
            ->assertOk()->assertJsonPath('ok', true)->json('id');

        // El pago queda en la tabla general: admin lo ve y el cliente pasa a Pagado.
        $factura = Factura::findOrFail($id);
        $this->assertEquals($corte->id, $factura->corte_caja_id);
        $this->assertSame(350.0, (float) $factura->total);
        $this->assertSame(1, $usuario->refresh()->estatus_servicio_id);

        // Un pago de otro perfil no se mezcla en el corte de mesas.
        $otro = User::factory()->create(['role' => 'pozo_hondo']);
        $corteOtro = CorteCaja::create([
            'user_id' => $otro->id, 'zona' => 'pozo_hondo', 'fecha_inicio' => now(), 'estado' => 'activo',
        ]);
        Factura::create([
            'reference_number' => 999, 'numero_servicio' => '8500', 'periodo' => '2026-10',
            'total' => 300, 'payload' => [], 'created_by' => $otro->id, 'corte_caja_id' => $corteOtro->id,
        ]);

        $this->getJson(route('mesas.corte.activo'))->assertOk()
            ->assertJsonPath('corte.total_pagos', 1)
            ->assertJsonPath('corte.total_recaudado', 300);

        $this->get(route('mesas.corte'))->assertOk()
            ->assertViewHas('totalComisionRecibo', 10)
            ->assertViewHas('pagos', fn ($pagos) => $pagos->count() === 1 && (float) $pagos->first()['total'] === 300.0);

        $excel = $this->get(route('mesas.corte.exportar'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Reporte de Corte de Caja - Mesas', $excel);
        $this->assertStringContainsString('290.00', $excel);

        $this->get(route('mesas.historial'))->assertOk()
            ->assertViewHas('pagos', fn ($pagos) => $pagos->count() === 1);

        $this->postJson(route('mesas.corte.finalizar'))->assertOk()
            ->assertJsonPath('corte.total_recaudado', 300)
            ->assertJsonPath('corte.total_pagos', 1);

        $this->get(route('mesas.historial-cortes'))->assertOk()
            ->assertViewHas('cortes', fn ($cortes) => $cortes->count() === 1);
        $this->getJson(route('mesas.corte.detalle', $corte->id))->assertOk()
            ->assertJsonPath('total_caja', 300)
            ->assertJsonPath('total_comision_recibo', 10);

        $this->postJson(route('mesas.corte.reanudar'))->assertOk()
            ->assertJsonPath('corte.id', $corte->id);
    }
}
