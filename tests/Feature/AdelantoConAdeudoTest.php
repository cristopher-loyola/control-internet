<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\User;
use App\Models\Usuario;
use App\Services\MorosidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdelantoConAdeudoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(9, 0));
        DB::table('estados')->updateOrInsert(['id' => 1], ['nombre' => 'Activado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Pagado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 4], ['nombre' => 'Pendiente de pago']);
        DB::table('servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Internet']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function cliente(array $attributes = []): Usuario
    {
        return Usuario::create(array_merge([
            'numero_servicio' => '8400', 'nombre_cliente' => 'Cliente adelanto',
            'domicilio' => 'Domicilio de prueba', 'tarifa' => 300,
            'estado_id' => 1, 'estatus_servicio_id' => 4, 'servicio_id' => 1,
            'proximo_pago' => '2026-09',
        ], $attributes));
    }

    private function pagar(float $total, int $meses = 1, bool $mesSiguiente = true): Factura
    {
        $id = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8400', 'total' => $total,
            'payload' => [
                'nombre' => 'Cliente adelanto', 'mensualidad' => 300,
                'recargo' => 'no', 'prepay' => 'si', 'prepay_months' => $meses,
                'prepay_total' => $meses === 6 ? 1620 : 300,
                'prepay_next_month' => $mesSiguiente,
            ],
        ])->assertOk()->json('id');

        return Factura::findOrFail($id);
    }

    private function saldoEn(string $fecha): float
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse($fecha));

        return app(MorosidadService::class)->calcularAdeudoUsuario('8400')['pendiente'];
    }

    public function test_pagar_trescientos_y_adelantar_un_mes_cubre_septiembre_y_octubre(): void
    {
        $usuario = $this->cliente();
        $factura = $this->pagar(600);
        $this->assertSame(600.0, (float) $factura->total);
        $this->assertSame('2026-11', $usuario->refresh()->proximo_pago);
        $this->assertSame(1, $usuario->estatus_servicio_id);
        $this->assertSame(0.0, $this->saldoEn('2026-09-07'));
        $this->assertSame(0.0, $this->saldoEn('2026-10-08'));
        $this->getJson(route('admin.pagos.prepay.status', ['numero' => '8400']))
            ->assertOk()->assertJsonPath('data.activo', true)->assertJsonPath('data.hasta_periodo', '2026-10');
        $this->getJson(route('admin.dashboard.metrics'))
            ->assertOk()->assertJsonPath('prepay_clients.0.vence_at', '2026-10-31');
        $this->assertSame(300.0, $this->saldoEn('2026-11-07'));
    }

    public function test_si_ya_pago_el_mes_actual_solo_cobra_el_mes_adelantado(): void
    {
        $usuario = $this->cliente(['proximo_pago' => '2026-10']);
        Factura::create([
            'numero_servicio' => '8400', 'reference_number' => 'PAGO-MENSUAL-PREVIO',
            'periodo' => '2026-09', 'total' => 300, 'payload' => [],
        ]);
        $factura = $this->pagar(300);
        $this->assertSame(300.0, (float) $factura->total);
        $this->assertSame('2026-11', $usuario->refresh()->proximo_pago);
        $this->assertSame(0.0, $this->saldoEn('2026-10-07'));
        $this->assertSame(300.0, $this->saldoEn('2026-11-07'));
    }

    public function test_dos_meses_pendientes_mas_uno_adelantado_cubren_hasta_octubre(): void
    {
        $this->cliente(['proximo_pago' => '2026-08']);
        $factura = $this->pagar(900);
        $this->assertSame(900.0, (float) $factura->total);
        $this->assertSame(0.0, $this->saldoEn('2026-10-07'));
        $this->assertSame(300.0, $this->saldoEn('2026-11-07'));
    }

    public function test_adelanto_no_pierde_cobertura_al_liquidar_un_adeudo_modificado(): void
    {
        $usuario = $this->cliente(['proximo_pago_monto' => 10]);
        $this->pagar(310);
        $this->assertSame('2026-11', $usuario->refresh()->proximo_pago);
        $this->assertNull($usuario->proximo_pago_monto);
        $this->assertSame(0.0, $this->saldoEn('2026-10-07'));
        $this->assertSame(300.0, $this->saldoEn('2026-11-07'));
    }

    public function test_descuento_de_seis_meses_cubre_octubre_a_marzo_incluso_con_primer_pago(): void
    {
        $usuario = $this->cliente(['primer_pago' => 10, 'primer_pago_periodo' => '2026-09']);
        $this->pagar(1630, 6);
        $this->assertSame('2027-04', $usuario->refresh()->proximo_pago);
        $this->assertSame(0.0, $this->saldoEn('2027-03-07'));
        $this->assertSame(300.0, $this->saldoEn('2027-04-07'));
    }

    public function test_cancelar_el_adelanto_restaura_el_adeudo_y_el_siguiente_pago(): void
    {
        $usuario = $this->cliente();
        $factura = $this->pagar(600);
        $this->post(route('admin.pagos.facturas.cancel', ['id' => $factura->id]), [
            'motivo' => 'Prueba de cancelación',
        ])->assertRedirect();
        $this->assertSame('2026-09', $usuario->refresh()->proximo_pago);
        $this->assertSame(300.0, $this->saldoEn('2026-09-07'));
        $this->assertSame(600.0, $this->saldoEn('2026-10-07'));
    }

    public function test_recibos_anteriores_sin_la_nueva_regla_conservan_su_cobertura(): void
    {
        $usuario = $this->cliente();
        $this->pagar(300, 1, false);
        $this->assertSame('2026-10', $usuario->refresh()->proximo_pago);
        $this->assertSame(300.0, $this->saldoEn('2026-10-07'));
    }
}
