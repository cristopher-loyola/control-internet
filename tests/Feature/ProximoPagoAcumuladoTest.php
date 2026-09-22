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
            'proximo_pago_monto' => $monto,
        ])->assertOk()->assertJsonPath('proximo_pago', now()->format('Y-m'));
    }

    private function adeudo(): array
    {
        return app(MorosidadService::class)->calcularAdeudoUsuario('8300');
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
