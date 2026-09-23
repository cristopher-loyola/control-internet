<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Usuario;
use App\Models\Factura;
use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use App\Services\MorosidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class TicketAdeudoRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear estados básicos
        Estado::create(['id' => 1, 'nombre' => 'Activado']);
        Estado::create(['id' => 2, 'nombre' => 'Desactivado']);
        
        // Crear estatus básicos
        EstatusServicio::create(['id' => 1, 'nombre' => 'Pagado']);
        EstatusServicio::create(['id' => 2, 'nombre' => 'Suspendido']);
        EstatusServicio::create(['id' => 3, 'nombre' => 'Cancelado']);
        EstatusServicio::create(['id' => 4, 'nombre' => 'Pendiente de pago']);

        // Crear servicios básicos
        Servicio::create(['id' => 1, 'nombre' => 'Internet']);
    }

    public function test_admin_pagos_view_incluye_campos_de_adeudo_en_ticket_y_recibo(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.pagos.index', absolute: false));

        $response->assertOk();
        $response->assertSee('Adeudo pendiente', escape: false);
        $response->assertSee('Saldo pendiente', escape: false);
        $response->assertSee('Total adelanto', escape: false);
    }

    public function test_pagos_recibos_view_incluye_campos_de_adeudo_en_ticket_y_recibo(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);

        $response = $this->actingAs($user)->get(route('pagos.recibos', absolute: false));

        $response->assertOk();
        $response->assertSee('Adeudo pendiente', escape: false);
        $response->assertSee('Saldo pendiente', escape: false);
        $response->assertSee('Total adelanto', escape: false);
    }

    public function test_morosidad_service_devuelve_lista_de_meses_adeudados(): void
    {
        $usuario = Usuario::create([
            'numero_servicio' => '1234',
            'nombre_cliente' => 'Test Client',
            'domicilio' => 'Calle Falsa 123',
            'tarifa' => 500,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);

        // Simular que el último pago fue hace 3 meses
        $periodoAnterior = now()->subMonths(3)->format('Y-m');
        Factura::create([
            'numero_servicio' => '1234',
            'periodo' => $periodoAnterior,
            'total' => 500,
            'reference_number' => 'REF001',
            'metodo' => 'Efectivo',
        ]);

        $service = new MorosidadService();
        $resultado = $service->calcularAdeudoUsuario('1234');

        $this->assertTrue($resultado['ok']);
        $this->assertGreaterThan(0, count($resultado['lista_meses']));
        
        // Verificar que los meses están en la lista
        $mesEsperado = now()->subMonths(2)->locale('es')->translatedFormat('F Y');
        $this->assertContains($mesEsperado, $resultado['lista_meses']);
    }

    public function test_descripcion_manual_saldada_no_oculta_un_pago_real_del_mes_anterior(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-02 09:00:00');

        $usuario = Usuario::create([
            'numero_servicio' => '7369',
            'nombre_cliente' => 'Cliente transferencia',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 400,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'adeudo_monto' => 0,
            'adeudo_descripcion' => 'Adelanto Julio',
        ]);

        Factura::create([
            'numero_servicio' => $usuario->numero_servicio,
            'periodo' => '2026-08',
            'total' => 400,
            'reference_number' => 'TRANSFER-7369',
            'payload' => ['metodo' => 'Deposito a cuenta'],
        ]);

        $service = new MorosidadService();
        $resultado = $service->calcularAdeudoUsuario('7369');

        $this->assertSame('2026-09', $resultado['desde_periodo']);
        $this->assertSame('septiembre 2026', $resultado['desde_mes_label']);
        $this->assertNull($resultado['descripcion_manual']);
        $this->assertSame([], $resultado['lista_meses']);
        $this->assertFalse($service->debeSerCortado($usuario, $resultado, '2026-09', 2));

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_ajuste_de_proximo_pago_respeta_cero_y_un_peso(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-02 09:00:00');

        foreach (['8100' => 0, '8101' => 1] as $numero => $montoFijado) {
            Usuario::create([
                'numero_servicio' => $numero,
                'nombre_cliente' => 'Cliente ajuste ' . $numero,
                'domicilio' => 'Domicilio de prueba',
                'tarifa' => 400,
                'estado_id' => 1,
                'estatus_servicio_id' => 1,
                'servicio_id' => 1,
                'proximo_pago' => '2026-09',
                'proximo_pago_monto' => $montoFijado,
            ]);

            Factura::create([
                'numero_servicio' => $numero,
                'periodo' => '2026-08',
                'total' => 400,
                'reference_number' => 'AJUSTE-' . $numero,
                'payload' => [],
            ]);
        }

        $service = new MorosidadService();
        $sinAdeudo = $service->calcularAdeudoUsuario('8100');
        $unPeso = $service->calcularAdeudoUsuario('8101');

        $this->assertSame(0.0, $sinAdeudo['pendiente']);
        $this->assertSame(0, $sinAdeudo['meses_adeudo']);
        $this->assertSame(1.0, $unPeso['pendiente']);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_guardar_cero_liquida_adeudo_y_programa_el_mes_siguiente(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-02 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $usuario = Usuario::create([
            'numero_servicio' => '8200',
            'nombre_cliente' => 'Cliente liquidado',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 400,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'adeudo_monto' => 400,
            'adeudo_descripcion' => 'Adeudo anterior',
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('admin.clientes.proximo-pago', ['id' => $usuario->id], absolute: false),
            ['proximo_pago' => '2026-09', 'proximo_pago_monto' => 0, 'modificado_por' => 'Responsable de prueba']
        );

        $response->assertOk()->assertJson([
            'ok' => true,
            'proximo_pago' => '2026-10',
            'proximo_pago_monto' => null,
        ]);
        $usuario->refresh();
        $this->assertSame(0.0, (float) $usuario->adeudo_monto);
        $this->assertNull($usuario->adeudo_descripcion);
        $this->assertSame(0.0, app(MorosidadService::class)->calcularAdeudoUsuario('8200')['pendiente']);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_recargo_del_primer_pago_no_reduce_la_mensualidad_siguiente(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-04 09:00:00');
        $usuario = Usuario::create([
            'numero_servicio' => '4779',
            'nombre_cliente' => 'Cliente nuevo con recargo',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 300,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'primer_pago' => 300,
            'primer_pago_periodo' => '2026-08',
            'primer_pago_vencimiento' => '2026-08-07',
            'fecha_contratacion' => '2026-08-01',
        ]);

        Factura::create([
            'usuario_id' => $usuario->id,
            'numero_servicio' => '4779',
            'periodo' => '2026-08',
            'total' => 350,
            'reference_number' => 'PRIMER-PAGO-CON-RECARGO',
            'payload' => [
                'mensualidad' => 300,
                'recargo' => 'si',
                'manual_total_enabled' => false,
            ],
        ]);

        $resultado = app(MorosidadService::class)->calcularAdeudoUsuario('4779');

        $this->assertSame(300.0, $resultado['mensualidad']);
        $this->assertSame(300.0, $resultado['pagado_parcial']);
        $this->assertSame(300.0, $resultado['pendiente']);
        $this->assertSame(1, $resultado['meses_adeudo']);
        $this->assertSame('2026-09', $resultado['desde_periodo']);
        $this->assertSame('2026-09', $resultado['desde_periodo_mostrado']);
        $this->assertFalse(
            app(MorosidadService::class)->debeSerCortado($usuario, $resultado, '2026-09', 30)
        );

        Factura::create([
            'usuario_id' => $usuario->id,
            'numero_servicio' => '4779',
            'periodo' => '2026-09',
            'total' => 300,
            'reference_number' => 'MENSUALIDAD-SIGUIENTE-COMPLETA',
            'payload' => ['mensualidad' => 300, 'recargo' => 'no'],
        ]);

        $liquidado = app(MorosidadService::class)->calcularAdeudoUsuario('4779');
        $this->assertSame(600.0, $liquidado['pagado_parcial']);
        $this->assertSame(0.0, $liquidado['pendiente']);
        $this->assertTrue($liquidado['cubierto_este_mes']);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_primer_pago_incompleto_del_mes_anterior_si_permanece_en_corte(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-04 09:00:00');
        $usuario = Usuario::create([
            'numero_servicio' => '1067',
            'nombre_cliente' => 'Cliente con agosto incompleto',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 300,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'primer_pago' => 100,
            'primer_pago_periodo' => '2026-08',
            'primer_pago_vencimiento' => '2026-08-07',
            'fecha_contratacion' => '2026-08-01',
        ]);

        Factura::create([
            'usuario_id' => $usuario->id,
            'numero_servicio' => '1067',
            'periodo' => '2026-08',
            'total' => 50,
            'reference_number' => 'PRIMER-PAGO-INCOMPLETO',
            'payload' => ['mensualidad' => 300, 'recargo' => 'no'],
        ]);

        $resultado = app(MorosidadService::class)->calcularAdeudoUsuario('1067');

        $this->assertSame('2026-08', $resultado['desde_periodo']);
        $this->assertSame(2, $resultado['meses_adeudo']);
        $this->assertSame(350.0, $resultado['pendiente']);
        $this->assertTrue(
            app(MorosidadService::class)->debeSerCortado($usuario, $resultado, '2026-09', 4)
        );

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_boton_azul_reemplaza_ochocientos_por_cuatrocientos_sin_importar_reglas_previas(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-04 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $usuario = Usuario::create([
            'numero_servicio' => '7044',
            'nombre_cliente' => 'Cliente ajuste total',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 400,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'adeudo_monto' => 800,
            'adeudo_descripcion' => 'Adeudo anterior',
            'proximo_pago' => '2026-11',
            'primer_pago' => 800,
            'primer_pago_periodo' => '2026-09',
        ]);

        $this->actingAs($admin)->postJson(
            route('admin.clientes.proximo-pago', ['id' => $usuario->id], absolute: false),
            [
                // Incluso si el navegador manda el periodo viejo, el boton
                // debe modificar el saldo que se esta mostrando ahora.
                'proximo_pago' => '2026-11',
                'proximo_pago_monto' => 400,
                'modificado_por' => 'Responsable de prueba',
                'adeudo_descripcion' => 'Ajuste autorizado',
            ]
        )->assertOk()->assertJson([
            'ok' => true,
            'proximo_pago' => '2026-09',
            'proximo_pago_monto' => 400,
            'pendiente' => 400,
        ]);

        $usuario->refresh();
        $this->assertSame(0.0, (float) $usuario->adeudo_monto);
        $this->assertSame('2026-09', $usuario->proximo_pago);
        $this->assertSame(400.0, (float) $usuario->proximo_pago_monto);

        $resultado = app(MorosidadService::class)->calcularAdeudoUsuario('7044');
        $this->assertSame(400.0, $resultado['pendiente']);
        $this->assertSame(0.0, $resultado['adeudo_manual']);
        $this->assertSame(0.0, $resultado['recargo']);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_guardar_monto_personalizado_permite_editar_descripcion(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-09-02 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $usuario = Usuario::create([
            'numero_servicio' => '8201',
            'nombre_cliente' => 'Cliente descripcion',
            'domicilio' => 'Domicilio de prueba',
            'tarifa' => 400,
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'adeudo_monto' => 0,
            'adeudo_descripcion' => 'Adeuda desde julio',
        ]);

        $this->actingAs($admin)->postJson(
            route('admin.clientes.proximo-pago', ['id' => $usuario->id], absolute: false),
            [
                'proximo_pago' => '2026-09',
                'proximo_pago_monto' => 1,
                'modificado_por' => 'Responsable de prueba',
                'adeudo_descripcion' => 'Ajuste autorizado de septiembre',
            ]
        )->assertOk()->assertJson([
            'ok' => true,
            'proximo_pago_monto' => 1,
            'adeudo_descripcion' => 'Ajuste autorizado de septiembre',
        ]);

        $resultado = app(MorosidadService::class)->calcularAdeudoUsuario('8201');
        $this->assertSame(1.0, $resultado['pendiente']);
        $this->assertSame('Ajuste autorizado de septiembre', $resultado['desde_mes_label']);

        $this->postJson(route('admin.pagos.facturas.store', absolute: false), [
            'numero_servicio' => '8201',
            'usuario_id' => $usuario->id,
            'total' => 1,
            'payload' => [
                'nombre' => 'Cliente descripcion',
                'mensualidad' => 400,
                'recargo' => 'no',
                'pago_anterior' => 0,
            ],
        ])->assertOk();

        $usuario->refresh();
        $this->assertSame('2026-10', $usuario->proximo_pago);
        $this->assertSame(400.0, (float) $usuario->proximo_pago_monto);
        $this->assertNull($usuario->adeudo_descripcion);
        $factura = Factura::where('numero_servicio', '8201')->latest('id')->firstOrFail();
        $this->assertSame('2026-09', $factura->periodo);
        $this->assertSame('Ajuste autorizado de septiembre', $factura->payload['ajuste_descripcion']);
        $this->assertTrue($factura->payload['ajuste_liquidado']);

        \Illuminate\Support\Carbon::setTestNow('2026-10-02 09:00:00');
        // En este fixture el fallback 4 corresponde a un catÃ¡logo distinto;
        // mantener el servicio activo para probar exclusivamente la recurrencia.
        $usuario->update(['estatus_servicio_id' => 1]);
        $octubre = app(MorosidadService::class)->calcularAdeudoUsuario('8201');
        $this->assertSame(400.0, $octubre['pendiente']);
        $this->assertSame('octubre 2026', $octubre['desde_mes_label']);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_rosalito_pagos_view_contiene_logica_de_otros_con_meses(): void
    {
        $user = User::factory()->create(['role' => 'rosalito']);
        $response = $this->actingAs($user)->get(route('rosalito.pagos'));
        $response->assertOk();
        $response->assertSee('Adeudos:', escape: false);
        $response->assertSee('lista_meses', escape: false);
    }

    public function test_pozo_hondo_pagos_view_contiene_logica_de_otros_con_meses(): void
    {
        $user = User::factory()->create(['role' => 'pozo_hondo']);
        $response = $this->actingAs($user)->get(route('pozo_hondo.pagos'));
        $response->assertOk();
        $response->assertSee('Adeudos:', escape: false);
        $response->assertSee('lista_meses', escape: false);
    }

    public function test_chivato_pagos_view_contiene_logica_de_otros_con_meses(): void
    {
        $user = User::factory()->create(['role' => 'chivato']);
        $response = $this->actingAs($user)->get(route('chivato.pagos'));
        $response->assertOk();
        $response->assertSee('Adeudos:', escape: false);
        $response->assertSee('lista_meses', escape: false);
    }
}
