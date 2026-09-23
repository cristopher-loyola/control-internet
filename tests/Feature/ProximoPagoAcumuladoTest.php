<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Factura;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Usuario;
use App\Services\MorosidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProximoPagoAcumuladoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(9, 0));
        Estado::unguarded(fn () => Estado::updateOrCreate(['id' => 1], ['nombre' => 'Activado']));
        EstatusServicio::unguarded(function () {
            EstatusServicio::updateOrCreate(['id' => 1], ['nombre' => 'Pagado']);
            EstatusServicio::updateOrCreate(['id' => 4], ['nombre' => 'Pendiente de pago']);
        });
        Servicio::unguarded(fn () => Servicio::updateOrCreate(['id' => 1], ['nombre' => 'Internet']));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function cliente(array $attributes = []): Usuario
    {
        return Usuario::create(array_merge([
            'numero_servicio' => '8300',
            'nombre_cliente' => 'Cliente ajuste acumulado',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 300,
            'estado_id' => 1,
            'estatus_servicio_id' => 4,
            'servicio_id' => 1,
            'proximo_pago' => '2026-08',
        ], $attributes));
    }

    private function ajustar(Usuario $usuario, float $monto): void
    {
        $this->postJson(route('admin.clientes.proximo-pago', ['id' => $usuario->id]), [
            'modificado_por' => 'Responsable de prueba',
            'proximo_pago_monto' => $monto,
        ])->assertOk()->assertJsonPath('proximo_pago', now()->format('Y-m'));
    }

    private function adeudo(): array
    {
        return app(MorosidadService::class)->calcularAdeudoUsuario('8300');
    }

    public static function botonesDeAjuste(): array
    {
        return [['azul'], ['modificar_total']];
    }

    #[DataProvider('botonesDeAjuste')]
    public function test_adeudo_650_ajustado_a_300_y_pagado_solo_debe_300_el_mes_siguiente(string $boton): void
    {
        $this->travelTo(now()->setDate(2026, 9, 23));
        $usuario = $this->cliente();
        $this->assertSame(650.0, $this->adeudo()['pendiente']);

        $payload = ['nombre' => $usuario->nombre_cliente, 'mensualidad' => 300, 'recargo' => 'no'];
        if ($boton === 'azul') {
            $this->ajustar($usuario, 300);
        } else {
            $payload = array_merge($payload, [
                'manual_total_enabled' => true,
                'manual_total_previous' => 650,
                'manual_total_value' => 300,
                'manual_total_reason' => 'Ajuste autorizado',
            ]);
        }
        $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8300', 'total' => 300, 'payload' => $payload,
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(0.0, $this->adeudo()['pendiente']);

        $this->travelTo(now()->setDate(2026, 10, 1));
        $this->getJson(route('admin.pagos.deuda', ['numero' => '8300']))
            ->assertOk()->assertJsonPath('pendiente', 300);
        $this->assertSame('2026-10', $this->adeudo()['desde_periodo']);

        $this->travelTo(now()->setDate(2026, 10, 8));
        $this->assertSame(350.0, $this->adeudo()['pendiente']);
        $this->assertSame(50.0, $this->adeudo()['recargo']);
    }

    public function test_adeudo_de_seiscientos_reemplazado_por_diez_acumula_solo_la_mensualidad_nueva(): void
    {
        $usuario = $this->cliente();
        $this->assertSame(600.0, $this->adeudo()['pendiente']);
        $this->ajustar($usuario, 10);
        $this->assertSame(10.0, $this->adeudo()['pendiente']);

        foreach ([1, 8, 31] as $dia) {
            $this->travelTo(now()->setDate(2026, 10, $dia));
            $adeudo = $this->adeudo();
            $recargo = $dia >= 8 ? 50.0 : 0.0;
            $this->assertSame(310.0 + $recargo, $adeudo['pendiente']);
            $this->assertSame($recargo, $adeudo['recargo']);
            $this->assertSame('2026-09', $adeudo['desde_periodo']);
            $this->assertSame(2, $adeudo['meses_adeudo']);
            $this->assertSame(['septiembre 2026'], $adeudo['lista_meses']);
            $this->assertTrue(app(MorosidadService::class)->debeSerCortado($usuario, $adeudo, '2026-10', $dia));
            $this->getJson(route('admin.pagos.deuda', ['numero' => '8300']))
                ->assertOk()->assertJsonPath('pendiente', (int) (310 + $recargo))
                ->assertJsonPath('recargo', (int) $recargo);
        }

        $this->travelTo(now()->setDate(2027, 1, 7));
        $this->assertSame(1210.0, $this->adeudo()['pendiente']);
    }

    public function test_ajuste_reemplaza_primer_pago_y_no_descuenta_otra_vez_facturas_del_mes_ajustado(): void
    {
        $usuario = $this->cliente([
            'primer_pago' => 600,
            'primer_pago_periodo' => '2026-08',
            'adeudo_monto' => 600,
        ]);
        Factura::create([
            'numero_servicio' => '8300',
            'reference_number' => 'PAGO-ANTES-DEL-AJUSTE',
            'periodo' => '2026-09',
            'total' => 300,
            'payload' => [],
        ]);
        $this->ajustar($usuario, 800);

        $this->travelTo(now()->setDate(2026, 10, 7));
        $this->assertSame(1100.0, $this->adeudo()['pendiente']);
        $this->assertSame(0.0, $this->adeudo()['adeudo_manual']);
    }

    public function test_pagos_posteriores_reducen_el_saldo_sin_contar_recargos_cancelados_o_periodos_futuros(): void
    {
        $this->ajustar($this->cliente(), 10);
        $this->travelTo(now()->setDate(2026, 10, 8));
        $factura = Factura::create([
            'numero_servicio' => '8300',
            'reference_number' => 'ABONO-CON-RECARGO',
            'periodo' => '2026-10',
            'total' => 150,
            'payload' => ['recargo' => 'si'],
        ]);
        Factura::create([
            'numero_servicio' => '8300',
            'reference_number' => 'PAGO-FUTURO',
            'periodo' => '2026-12',
            'total' => 300,
            'payload' => [],
        ]);

        $adeudo = $this->adeudo();
        $this->assertSame(260.0, $adeudo['pendiente']);
        $this->assertSame(100.0, $adeudo['pagado_parcial']);
        $this->assertSame('2026-10', $adeudo['desde_periodo']);
        $this->assertSame(1, $adeudo['meses_adeudo']);
        $this->assertSame([], $adeudo['lista_meses']);

        $factura->delete();
        $this->assertSame(360.0, $this->adeudo()['pendiente']);
    }

    public function test_pagar_el_saldo_acumulado_y_cancelar_el_recibo_conserva_el_ajuste_original(): void
    {
        $usuario = $this->cliente();
        $this->ajustar($usuario, 10);
        $this->travelTo(now()->setDate(2026, 10, 7));

        $id = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8300',
            'usuario_id' => $usuario->id,
            'total' => 310,
            'payload' => ['nombre' => $usuario->nombre_cliente, 'mensualidad' => 300, 'recargo' => 'no'],
        ])->assertOk()->json('id');

        $this->assertSame(0.0, $this->adeudo()['pendiente']);
        $this->assertTrue($this->adeudo()['cubierto_este_mes']);
        $this->assertSame(1, $usuario->refresh()->estatus_servicio_id);

        $this->travelTo(now()->setDate(2026, 11, 7));
        $this->assertSame(300.0, $this->adeudo()['pendiente']);

        $this->post(route('admin.pagos.facturas.cancel', ['id' => $id]), [
            'motivo' => 'Prueba de restauración del ajuste',
        ])->assertRedirect();
        $this->assertSame(610.0, $this->adeudo()['pendiente']);
    }

    public function test_boton_azul_registra_cada_cambio_y_lo_exporta_en_azul_sin_cobro(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 23));
        $usuario = $this->cliente();
        $this->assertSame(650.0, $this->adeudo()['pendiente']);
        foreach ([100, 0] as $monto) {
            $this->postJson(route('admin.clientes.proximo-pago', $usuario->id), [
                'modificado_por' => '  Ana López  ',
                'proximo_pago_monto' => $monto,
                'adeudo_descripcion' => 'Ajuste azul a '.$monto,
            ])->assertOk();
        }

        $logs = DB::table('audit_logs')->where('action', 'cliente_adeudo_override')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals(600, json_decode($logs[0]->prev_values, true)['total']);
        $this->assertEquals(100, json_decode($logs[0]->new_values, true)['total']);
        $this->assertSame('Ana López', json_decode($logs[0]->new_values, true)['modificado_por']);
        $this->assertSame(auth()->user()->name, $logs[0]->actor_name);
        $this->assertEquals(100, json_decode($logs[1]->prev_values, true)['total']);
        $this->assertEquals(0, json_decode($logs[1]->new_values, true)['total']);
        $this->assertDatabaseCount('facturas', 0);

        // Los cambios posteriores del cliente no alteran las capturas históricas.
        $usuario->update(['nombre_cliente' => 'Nombre cambiado después']);
        Factura::create([
            'numero_servicio' => '9999', 'reference_number' => 'REPORTE',
            'periodo' => '2026-09', 'total' => 150,
            'payload' => ['manual_total_enabled' => true, 'manual_total_previous' => 300, 'cobro' => 'Alan'],
        ]);
        $this->travelTo(now()->setDate(2026, 10, 1));
        $this->ajustar($usuario, 80);

        $response = $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09']))
            ->assertOk()->assertViewHas('cantidad', 3)
            ->assertSee('Clientes (botón azul)')->assertSee('Pagos (Modificar total)')
            ->assertSee('Cliente ajuste acumulado')->assertDontSee('Nombre cambiado después')
            ->assertSee('Ajuste azul a 0')->assertSee('No aplica')->assertSee('Alan')->assertSee('Ana López');
        $this->assertSame(2, substr_count($response->getContent(), 'background-color: #dbeafe;'));
        $this->assertCount(2, $response->viewData('rows')->where('es_cliente', true));
        $excel = $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-09', 'format' => 'excel']))->assertOk();
        $html = $excel->streamedContent();
        $this->assertSame(2, substr_count($html, 'background-color: #dbeafe;'));
        foreach (['Ajuste azul a 100', 'Ajuste azul a 0', '$600.00', '$100.00', '$0.00', 'No aplica', 'Alan', 'Ana López'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->assertStringNotContainsString('Nombre cambiado después', $html);
        $this->get(route('admin.dashboard.modificaciones', ['mes' => '2026-10']))
            ->assertOk()->assertViewHas('cantidad', 1);
    }

    public function test_ajuste_invalido_y_cargo_naranja_no_se_registran_como_modificacion_azul(): void
    {
        $usuario = $this->cliente();
        $this->postJson(route('admin.clientes.proximo-pago', $usuario->id), [
            'modificado_por' => 'Responsable de prueba',
            'proximo_pago_monto' => -10,
        ])->assertUnprocessable();
        $this->postJson(route('admin.clientes.cargo-extra', $usuario->id), [
            'monto' => 50, 'descripcion' => 'Cable',
        ])->assertOk();
        $this->assertSame(0, DB::table('audit_logs')->where('action', 'cliente_adeudo_override')->count());
    }

    public function test_boton_azul_exige_nombre_sin_cambiar_el_adeudo_si_falta(): void
    {
        $usuario = $this->cliente();
        foreach ([null, '', '   ', str_repeat('a', 151), ['nombre' => 'Ana']] as $nombre) {
            $datos = ['proximo_pago_monto' => 100];
            if ($nombre !== null) {
                $datos['modificado_por'] = $nombre;
            }
            $this->postJson(route('admin.clientes.proximo-pago', $usuario->id), $datos)
                ->assertUnprocessable()->assertJsonValidationErrors('modificado_por');
            $this->assertSame(600.0, $this->adeudo()['pendiente']);
            $this->assertNull($usuario->refresh()->proximo_pago_monto);
        }
        $this->assertSame(0, DB::table('audit_logs')->where('action', 'cliente_adeudo_override')->count());
        $this->get(route('admin.clientes.index'))->assertOk()
            ->assertSee('Nombre de quien modifica')->assertSee('name="modificado_por" type="text" required', false);
    }

    public function test_un_nuevo_ajuste_reemplaza_el_saldo_acumulado(): void
    {
        $usuario = $this->cliente();
        $this->ajustar($usuario, 10);
        $this->travelTo(now()->setDate(2026, 10, 7));
        $this->ajustar($usuario, 20);

        $this->travelTo(now()->setDate(2026, 11, 7));
        $this->assertSame(320.0, $this->adeudo()['pendiente']);
        $this->assertSame('2026-10', $this->adeudo()['desde_periodo']);
    }
}
