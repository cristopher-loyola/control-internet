<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\Factura;
use App\Models\User;
use App\Models\Usuario;
use App\Services\MorosidadService;
use App\Services\WhatsAppNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdelantoZonasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-07 09:00:00'));
        DB::table('estados')->updateOrInsert(['id' => 1], ['nombre' => 'Activado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Pagado']);
        DB::table('estatus_servicios')->updateOrInsert(['id' => 4], ['nombre' => 'Pendiente de pago']);
        DB::table('servicios')->updateOrInsert(['id' => 1], ['nombre' => 'Internet']);
        $this->mock(WhatsAppNotifierService::class)
            ->shouldReceive('enviarNotificacionReactivacion')->andReturn(false);
    }

    public static function zonas(): array
    {
        return [['chivato'], ['pozo_hondo'], ['rosalito']];
    }

    public static function escenarios(): array
    {
        $casos = [
            'mes actual y un adelanto' => [[], 1, 300, 600, '2026-11'],
            'dos pendientes y un adelanto' => [['proximo_pago' => '2026-08'], 1, 300, 900, '2026-11'],
            'saldo ajustado y un adelanto' => [['proximo_pago_monto' => 10], 1, 300, 310, '2026-11'],
            'primer pago y seis meses con descuento' => [['primer_pago' => 10, 'primer_pago_periodo' => '2026-09'], 6, 1620, 1630, '2027-04'],
            'mes actual y doce meses con descuento' => [[], 12, 3024, 3324, '2027-10'],
        ];
        $result = [];
        foreach (self::zonas() as [$zona]) {
            foreach ($casos as $nombre => $caso) {
                $result[$zona.' '.$nombre] = [$zona, ...$caso];
            }
        }

        return $result;
    }

    private function iniciarCaja(string $zona): CorteCaja
    {
        $cajero = User::factory()->create(['role' => $zona]);
        $this->actingAs($cajero);

        return CorteCaja::create([
            'user_id' => $cajero->id, 'zona' => $zona,
            'fecha_inicio' => now(), 'estado' => 'activo',
        ]);
    }

    private function cliente(array $attributes = []): Usuario
    {
        return Usuario::create(array_merge([
            'numero_servicio' => '8400', 'nombre_cliente' => 'Cliente adelanto zona',
            'domicilio' => 'Domicilio de prueba', 'tarifa' => 300,
            'estado_id' => 1, 'estatus_servicio_id' => 4, 'servicio_id' => 1,
            'proximo_pago' => '2026-09',
        ], $attributes));
    }

    private function datosPago(float $total = 600, int $meses = 1, float $adelanto = 300): array
    {
        return [
            'numero_servicio' => '8400', 'total' => $total,
            'payload' => [
                'nombre' => 'Cliente adelanto zona', 'mensualidad' => 300,
                'recargo' => 'no', 'metodo' => 'Efectivo', 'otro' => 'no',
                'prepay' => 'si', 'prepay_months' => $meses,
                'prepay_total' => $adelanto, 'prepay_next_month' => true,
            ],
        ];
    }

    #[DataProvider('escenarios')]
    public function test_cobra_adeudo_y_adelanto_con_la_misma_cobertura_del_admin(
        string $zona, array $attributes, int $meses, float $adelanto, float $total, string $proximo
    ): void {
        $corte = $this->iniciarCaja($zona);
        $usuario = $this->cliente($attributes);
        $id = $this->postJson(route($zona.'.recibos.facturas.store'), $this->datosPago($total, $meses, $adelanto))
            ->assertOk()->assertJsonPath('ok', true)->json('id');

        $factura = Factura::findOrFail($id);
        $this->assertSame($total, (float) $factura->total);
        $this->assertEquals($corte->id, $factura->corte_caja_id);
        $this->assertEquals($corte->user_id, $factura->created_by);
        $this->assertSame($proximo, $usuario->refresh()->proximo_pago);
        $this->assertNull($usuario->proximo_pago_monto);
        $this->assertSame(1, $usuario->estatus_servicio_id);
        $this->assertSame('2026-09', $factura->periodo);
        $this->getJson(route($zona.'.recibos.facturas.verificar', $id))
            ->assertOk()->assertJsonPath('existe', true);

        $ultimoCubierto = Carbon::parse($proximo.'-01')->subMonth();
        $this->travelTo($ultimoCubierto->copy()->day(8));
        $this->getJson(route($zona.'.recibos.prepay.status', ['numero' => '8400']))
            ->assertOk()->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.hasta_periodo', $ultimoCubierto->format('Y-m'));
        $this->assertSame(0.0, app(MorosidadService::class)->calcularAdeudoUsuario('8400')['pendiente']);
        $this->travelTo(Carbon::parse($proximo.'-07'));
        $this->assertSame(300.0, app(MorosidadService::class)->calcularAdeudoUsuario('8400')['pendiente']);
    }

    #[DataProvider('zonas')]
    public function test_permite_adelantar_despues_del_pago_mensual_y_bloquea_otro_adelanto_vigente(string $zona): void
    {
        $corte = $this->iniciarCaja($zona);
        $usuario = $this->cliente();
        $mensual = $this->datosPago(300);
        $mensual['payload'] = ['nombre' => 'Cliente adelanto zona', 'mensualidad' => 300, 'recargo' => 'no', 'metodo' => 'Efectivo'];
        $this->postJson(route($zona.'.recibos.facturas.store'), $mensual)->assertOk();
        $adelanto = $this->datosPago(300);
        $adelanto['payload']['pago_anterior'] = 300;
        $this->postJson(route($zona.'.recibos.facturas.store'), $adelanto)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('2026-11', $usuario->refresh()->proximo_pago);
        $this->assertSame(2, $corte->facturas()->count());
        $this->assertSame(600.0, (float) $corte->facturas()->sum('total'));
        $this->postJson(route($zona.'.recibos.facturas.store'), $adelanto)->assertConflict();
        $this->postJson(route($zona.'.recibos.facturas.store'), $mensual)->assertConflict();
        $this->assertDatabaseCount('facturas', 2);

        $this->travelTo(Carbon::parse('2026-11-07'));
        $this->postJson(route($zona.'.recibos.facturas.store'), $this->datosPago())->assertOk();
        $this->assertSame('2027-01', $usuario->refresh()->proximo_pago);
    }

    #[DataProvider('zonas')]
    public function test_adelanto_no_reutiliza_la_factura_del_admin_con_el_mismo_importe_y_pago_anterior(string $zona): void
    {
        $this->cliente();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $mensual = $this->datosPago(300);
        $mensual['payload']['prepay'] = 'no';
        $mensual['payload']['pago_anterior'] = 300;
        $previo = $this->postJson(route('admin.pagos.facturas.store'), $mensual)->assertOk()->json('id');

        $corte = $this->iniciarCaja($zona);
        $adelanto = $this->datosPago(300);
        $adelanto['payload']['pago_anterior'] = 300;
        $id = $this->postJson(route($zona.'.recibos.facturas.store'), $adelanto)
            ->assertOk()->assertJsonPath('ok', true)->json('id');

        $this->assertNotSame($previo, $id);
        $this->assertNull(Factura::findOrFail($previo)->corte_caja_id);
        $this->assertEquals($admin->id, Factura::findOrFail($previo)->created_by);
        $this->assertDatabaseHas('facturas', ['id' => $id, 'total' => 300, 'created_by' => $corte->user_id, 'corte_caja_id' => $corte->id]);
        $this->getJson(route($zona.'.recibos.facturas.verificar', $id))->assertOk()->assertJsonPath('existe', true);
        $this->get(route($zona.'.corte'))->assertOk()->assertViewHas('pagos', fn ($pagos) => $pagos->sole()['total'] === 300.0);
    }

    #[DataProvider('zonas')]
    public function test_corte_no_descuenta_recargo_de_un_adelanto_que_solo_cobro_la_mensualidad(string $zona): void
    {
        $corte = $this->iniciarCaja($zona);
        $this->cliente();
        $datos = $this->datosPago(300);
        $datos['payload']['recargo'] = 'si';
        $datos['payload']['adeudo_pendiente'] = 0;
        $factura = Factura::create(array_merge($datos, [
            'reference_number' => '12226', 'periodo' => '2026-09', 'created_by' => $corte->user_id,
        ]));
        $factura->corte_caja_id = $corte->id;
        $factura->save();

        $this->get(route($zona.'.corte'))->assertOk()->assertViewHas('pagos', fn ($pagos) => $pagos->sole()['total'] === 300.0);
        $this->getJson(route($zona.'.corte.activo'))->assertOk()->assertJsonPath('corte.total_recaudado', 300);
        $this->assertSame('si', $factura->refresh()->payload['recargo']);
        $this->assertSame(300.0, (float) $factura->total);
    }

    #[DataProvider('zonas')]
    public function test_eliminar_adelanto_restaura_el_saldo_y_el_proximo_pago(string $zona): void
    {
        $this->iniciarCaja($zona);
        $usuario = $this->cliente(['proximo_pago_monto' => 10]);
        $datos = $this->datosPago(310);
        $id = $this->postJson(route($zona.'.recibos.facturas.store'), $datos)->assertOk()->json('id');
        $this->delete(route($zona.'.pagos.eliminar', $id))->assertRedirect();
        $this->assertSame('2026-09', $usuario->refresh()->proximo_pago);
        $this->assertSame(10.0, (float) $usuario->proximo_pago_monto);
        $this->assertSame(4, $usuario->estatus_servicio_id);
        $this->assertSame(10.0, app(MorosidadService::class)->calcularAdeudoUsuario('8400')['pendiente']);
        $this->getJson(route($zona.'.recibos.prepay.status', ['numero' => '8400']))
            ->assertOk()->assertJsonPath('data.activo', false);
        $this->postJson(route($zona.'.recibos.facturas.store'), $datos)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('2026-11', $usuario->refresh()->proximo_pago);
    }

    #[DataProvider('zonas')]
    public function test_el_perfil_muestra_la_opcion_de_adelanto_y_los_doce_plazos(string $zona): void
    {
        $this->iniciarCaja($zona);
        $response = $this->get(route($zona.'.pagos'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $selector = $xpath->query('//select[@x-model="form.prepay"]')->item(0);
        $this->assertNotNull($selector);
        $this->assertSame(0, $xpath->query('ancestor::*[contains(concat(" ", normalize-space(@class), " "), " hidden ")]', $selector)->length);
        $this->assertSame(12, $xpath->query('//select[@x-model.number="form.prepay_months"]/option')->length);
        $this->getJson(route($zona.'.prepay.settings'))->assertOk()
            ->assertJsonPath('matrix.6.percent', 10)->assertJsonPath('matrix.12.percent', 16);
    }

    #[DataProvider('zonas')]
    public function test_requiere_corte_de_caja_activo_para_adelantar(string $zona): void
    {
        $this->actingAs(User::factory()->create(['role' => $zona]));
        $this->cliente();
        $this->postJson(route($zona.'.recibos.facturas.store'), $this->datosPago())->assertUnprocessable();
        $this->assertDatabaseCount('facturas', 0);
    }
}
