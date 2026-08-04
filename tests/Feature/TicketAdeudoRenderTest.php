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
