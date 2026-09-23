<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\Factura;
use App\Models\User;
use App\Models\Usuario;
use App\Services\MorosidadService;
use App\Services\WhatsAppNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagoSaldoAjustadoTest extends TestCase
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
        $this->mock(WhatsAppNotifierService::class)->shouldReceive('enviarNotificacionReactivacion')->andReturn(false);
    }

    private function cliente(array $attributes = []): Usuario
    {
        return Usuario::create(array_merge([
            'numero_servicio' => '8500', 'nombre_cliente' => 'Cliente ajuste',
            'domicilio' => 'Prueba', 'tarifa' => 300, 'estado_id' => 1,
            'estatus_servicio_id' => 4, 'servicio_id' => 1,
            'adeudo_monto' => 900, 'adeudo_descripcion' => 'Adeudo anterior',
            'proximo_pago' => '2026-08',
        ], $attributes));
    }

    private function ajustar(Usuario $usuario, float $monto): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.clientes.proximo-pago', $usuario->id), [
            'proximo_pago_monto' => $monto, 'adeudo_descripcion' => 'Ajuste autorizado',
        ])->assertOk();
    }

    private function deuda(): array
    {
        return app(MorosidadService::class)->calcularAdeudoUsuario('8500');
    }

    public static function canales(): array
    {
        return [['admin'], ['pagos'], ['chivato'], ['pozo_hondo'], ['rosalito'], ['transferencia']];
    }

    public function test_monto_fijo_conserva_descripcion_y_aplica_recargo_desde_el_dia_ocho(): void
    {
        $usuario = $this->cliente(['tarifa' => 500]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.clientes.proximo-pago', $usuario->id), [
            'proximo_pago_monto' => 1000,
            'adeudo_descripcion' => 'Agosto',
        ])->assertOk();

        foreach ([7 => 0, 8 => 50, 21 => 50] as $dia => $recargo) {
            $this->travelTo(now()->setDate(2026, 9, $dia));
            $this->getJson(route('admin.pagos.deuda', ['numero' => '8500']))
                ->assertOk()
                ->assertJsonPath('pendiente', 1000 + $recargo)
                ->assertJsonPath('recargo', $recargo)
                ->assertJsonPath('desde_mes_label', 'Agosto')
                ->assertJsonPath('descripcion_manual', 'Agosto');
            $this->assertSame(1000.0, (float) $usuario->refresh()->proximo_pago_monto);
        }
    }

    public static function opcionesRecargo(): array
    {
        return [['si', 1050.0], ['no', 1000.0]];
    }

    #[DataProvider('opcionesRecargo')]
    public function test_pagar_monto_fijo_respeta_selector_y_conserva_descripcion_del_banner(string $recargo, float $total): void
    {
        $this->travelTo(now()->setDate(2026, 9, 21));
        $usuario = $this->cliente([
            'tarifa' => 500, 'adeudo_monto' => 0, 'adeudo_descripcion' => 'Agosto',
            'proximo_pago' => '2026-09', 'proximo_pago_monto' => 1000,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8500', 'total' => $total,
            'payload' => [
                'mensualidad' => 500, 'recargo' => $recargo,
                'adeudo_pendiente' => 1050,
                'adeudo_desde_label' => 'Agosto', 'adeudo_descripcion' => 'Agosto',
            ],
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(0.0, $this->deuda()['pendiente']);
        $this->assertSame(0.0, $this->deuda()['recargo']);
        $this->assertNull($usuario->refresh()->adeudo_descripcion);
        $this->travelTo(now()->setDate(2026, 10, 8));
        $siguienteMes = $this->deuda();
        $this->assertSame(550.0, $siguienteMes['pendiente']);
        $this->assertSame('octubre 2026', $siguienteMes['desde_mes_label']);
        $this->assertNull($siguienteMes['descripcion_manual']);
        $usuario->update(['adeudo_descripcion' => 'Nueva descripción']);
        foreach ([
            route('admin.pagos.facturas.show', ['id' => $response->json('id')]),
            route('admin.pagos.facturas.by_folio', ['ref' => $response->json('referencia')]),
        ] as $url) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath('data.payload.recargo', $recargo)
                ->assertJsonPath('data.payload.adeudo_desde_label', 'Agosto')
                ->assertJsonPath('data.payload.adeudo_descripcion', 'Agosto');
        }
    }

    public function test_cargo_extra_no_incorpora_el_recargo_a_la_base_fija(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 21));
        $usuario = $this->cliente([
            'adeudo_monto' => 0, 'proximo_pago' => '2026-09', 'proximo_pago_monto' => 1000,
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.clientes.cargo-extra', $usuario->id), [
            'monto' => 100, 'descripcion' => 'Cable',
        ])->assertOk();

        $this->assertSame(1100.0, (float) $usuario->refresh()->proximo_pago_monto);
        $this->assertSame(1150.0, $this->deuda()['pendiente']);
        $this->assertSame(50.0, $this->deuda()['recargo']);
    }

    #[DataProvider('canales')]
    public function test_pago_posterior_al_ajuste_liquida_sin_revivir_la_deuda_anterior(string $canal): void
    {
        $usuario = $this->cliente(['primer_pago' => 900, 'primer_pago_periodo' => '2026-08']);
        // Un pago anterior del mismo mes queda incluido en el saldo reemplazado.
        $anterior = Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'ANTERIOR',
            'periodo' => '2026-09', 'total' => 100, 'payload' => [],
        ]);
        $this->ajustar($usuario, 100);
        $this->assertSame(100.0, $this->deuda()['pendiente']);

        $cajero = User::factory()->create(['role' => $canal === 'transferencia' ? 'admin' : $canal]);
        $this->actingAs($cajero);
        if (in_array($canal, ['chivato', 'pozo_hondo', 'rosalito'])) {
            CorteCaja::create(['user_id' => $cajero->id, 'zona' => $canal, 'fecha_inicio' => now(), 'estado' => 'activo']);
        }
        if ($canal === 'transferencia') {
            $this->postJson(route('admin.transferencias.registrar'), ['pagos' => [[
                'numero_servicio' => '8500', 'monto' => 100, 'periodo' => '2026-09',
            ]]])->assertOk()->assertJsonPath('resultados.0.ok', true);
        } else {
            $route = $canal === 'admin' ? 'admin.pagos.facturas.store' : $canal.'.recibos.facturas.store';
            $this->postJson(route($route), [
                'numero_servicio' => '8500', 'usuario_id' => $usuario->id, 'total' => 100,
                'payload' => ['nombre' => 'Cliente ajuste', 'mensualidad' => 300, 'recargo' => 'no'],
            ])->assertOk()->assertJsonPath('ok', true);
        }
        $this->assertSame(0.0, $this->deuda()['pendiente']);
        $this->assertSame([], $this->deuda()['lista_meses']);
        $this->assertSame(1, $usuario->refresh()->estatus_servicio_id);
        $this->assertSame(0.0, (float) $usuario->adeudo_monto);
        $pagada = Factura::where('numero_servicio', '8500')->latest('id')->firstOrFail();
        $this->assertNotSame($anterior->id, $pagada->id);

        $this->travelTo(now()->setDate(2026, 10, 7));
        $this->assertSame(300.0, $this->deuda()['pendiente']);
        $this->assertSame('2026-10', $this->deuda()['desde_periodo']);

        // Cancelar restaura el importe ajustado, no los $900 anteriores.
        if (in_array($canal, ['chivato', 'pozo_hondo', 'rosalito'])) {
            $this->delete(route($canal.'.pagos.eliminar', $pagada->id))->assertRedirect();
        } else {
            $this->actingAs(User::factory()->create(['role' => 'admin']));
            $this->post(route('admin.pagos.facturas.cancel', $pagada->id), ['motivo' => 'Prueba'])->assertRedirect();
        }
        $this->assertSame(400.0, $this->deuda()['pendiente']);
    }

    public function test_transferencia_de_agosto_capturada_en_septiembre_liquida_el_ajuste_de_agosto(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 7));
        $usuario = $this->cliente();
        $this->ajustar($usuario, 300);
        $this->travelTo(now()->setDate(2026, 9, 2));
        $this->postJson(route('admin.transferencias.registrar'), [
            'mes_contable' => '2026-08',
            'pagos' => [['numero_servicio' => '8500', 'monto' => 300, 'periodo' => '2026-08']],
        ])->assertOk()->assertJsonPath('resultados.0.ok', true);
        $this->assertSame(300.0, $this->deuda()['pendiente']);
        $this->assertSame('2026-09', $this->deuda()['desde_periodo']);
        $this->assertSame([], $this->deuda()['lista_meses']);
    }

    public function test_transferencia_parcial_no_condona_el_resto_del_ajuste(): void
    {
        $this->ajustar($this->cliente(), 300);
        $this->postJson(route('admin.transferencias.registrar'), ['pagos' => [[
            'numero_servicio' => '8500', 'monto' => 100, 'periodo' => '2026-09',
        ]]])->assertOk()->assertJsonPath('resultados.0.ok', true);
        $this->assertSame(200.0, $this->deuda()['pendiente']);
    }

    public function test_recibo_existente_liquida_el_saldo_heredado_sin_marcador_de_ajuste(): void
    {
        $this->cliente(['adeudo_monto' => 0, 'proximo_pago_monto' => 650]);
        $factura = Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'LEGADO',
            'periodo' => '2026-08', 'total' => 650, 'payload' => ['recargo' => 'si'],
        ]);
        $this->assertSame(300.0, $this->deuda()['pendiente']);
        $this->assertSame('2026-09', $this->deuda()['desde_periodo']);
        $factura->delete();
        $this->assertSame(950.0, $this->deuda()['pendiente']);
    }

    public function test_nuevo_ajuste_despues_de_pagar_no_se_liquida_con_el_recibo_anterior(): void
    {
        $usuario = $this->cliente();
        $this->ajustar($usuario, 100);
        Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'PRIMER-AJUSTE',
            'periodo' => '2026-09', 'total' => 100, 'payload' => [],
        ]);
        $this->assertSame(0.0, $this->deuda()['pendiente']);
        $this->ajustar($usuario, 50);
        $this->assertSame(50.0, $this->deuda()['pendiente']);
        $this->travelTo(now()->setDate(2026, 10, 7));
        $this->assertSame(350.0, $this->deuda()['pendiente']);
    }

    public function test_cancelar_un_pago_anterior_a_un_nuevo_ajuste_no_restaura_la_deuda_reemplazada(): void
    {
        $usuario = $this->cliente();
        $this->ajustar($usuario, 100);
        $id = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8500', 'total' => 100,
            'payload' => ['mensualidad' => 300, 'recargo' => 'no'],
        ])->assertOk()->json('id');
        $this->ajustar($usuario, 20);
        $this->post(route('admin.pagos.facturas.cancel', $id), ['motivo' => 'Prueba'])->assertRedirect();
        $this->assertSame(20.0, $this->deuda()['pendiente']);
    }

    public function test_recargo_o_cargo_viejo_del_recibo_no_se_convierte_en_adelanto(): void
    {
        $this->cliente(['adeudo_monto' => 0, 'proximo_pago_monto' => 300]);
        Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'LEGADO-CON-CARGOS',
            'periodo' => '2026-08', 'total' => 450, 'payload' => ['recargo' => 'si'],
        ]);
        $this->assertSame(300.0, $this->deuda()['pendiente']);
    }

    public function test_cargo_extra_despues_de_pagar_no_vuelve_a_cobrar_el_ajuste_liquidado(): void
    {
        $usuario = $this->cliente();
        $this->ajustar($usuario, 100);
        Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'PAGO-ANTES-DE-CARGO',
            'periodo' => '2026-09', 'total' => 100, 'payload' => [],
        ]);
        $this->postJson(route('admin.clientes.cargo-extra', $usuario->id), [
            'monto' => 50, 'descripcion' => 'Cable',
        ])->assertOk();
        $this->assertSame(50.0, $this->deuda()['pendiente']);
    }

    public static function modificacionesEnHistorial(): array
    {
        return [
            'total manual sin adeudo previo' => [['manual_total_enabled' => true, 'manual_total_reason' => 'Ajuste autorizado'], true],
            'adeudo previo sin usar el boton' => [['adeudo_monto_previo' => 900], false],
            'total manual con adeudo previo' => [['manual_total_enabled' => true, 'manual_total_reason' => 'Ajuste autorizado', 'adeudo_monto_previo' => 900], true],
            'pago normal' => [[], false],
            'edicion desactivada' => [['manual_total_enabled' => false, 'manual_total_reason' => 'Motivo sin aplicar', 'adeudo_monto_previo' => 900], false],
        ];
    }

    #[DataProvider('modificacionesEnHistorial')]
    public function test_historial_identifica_modificaciones_guardadas(array $payload, bool $modificado): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Factura::create([
            'numero_servicio' => '8500', 'reference_number' => 'HISTORIAL',
            'periodo' => '2026-09', 'total' => 159, 'payload' => $payload,
        ]);

        $response = $this->get(route('admin.pagos.historial'))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->first()->adeudo_modificado === $modificado);

        if ($modificado) {
            $response->assertSee('Modificó adeudo');
        } else {
            $response->assertDontSee('Modificó adeudo')->assertDontSee('Motivo sin aplicar');
        }
        if (!empty($payload['manual_total_enabled'])) {
            $response->assertSee('Ajuste autorizado')->assertSee('Total modificado manualmente a $159.00');
        }
    }

    public function test_modificar_total_en_el_recibo_liquida_el_adeudo_con_el_importe_autorizado(): void
    {
        $usuario = $this->cliente(['primer_pago' => 900, 'primer_pago_periodo' => '2026-08']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '8500', 'total' => 1200,
            'payload' => [
                'mensualidad' => 300, 'recargo' => 'no', 'manual_total_enabled' => true,
                'manual_total_value' => 100, 'manual_total_reason' => 'Corrección autorizada',
            ],
        ])->assertOk();
        $this->get(route('admin.pagos.historial'))->assertOk()
            ->assertSee('Modificó adeudo')->assertSee('Corrección autorizada');
        $this->assertSame(0.0, $this->deuda()['pendiente']);
        $this->assertSame(0.0, (float) $usuario->refresh()->adeudo_monto);
        $this->travelTo(now()->setDate(2026, 10, 7));
        $this->assertSame(300.0, $this->deuda()['pendiente']);
    }
}
