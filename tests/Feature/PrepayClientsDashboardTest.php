<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Factura;
use App\Models\User;
use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use Illuminate\Support\Carbon;

class PrepayClientsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup base data
        Estado::create(['id' => 1, 'nombre' => 'Activado']);
        EstatusServicio::create(['id' => 1, 'nombre' => 'Activo']);
        EstatusServicio::create(['id' => 2, 'nombre' => 'Cancelado']);
        Servicio::create(['id' => 1, 'nombre' => 'Internet Residencial']);
    }

    public function test_dashboard_returns_prepay_clients()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a user
        $usuario = Usuario::create([
            'numero_servicio' => '1001',
            'nombre_cliente' => 'Test Prepay Client',
            'tarifa' => '300',
            'domicilio' => 'Calle 123',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);

        // Create a prepay invoice
        Factura::create([
            'reference_number' => 1,
            'numero_servicio' => '1001',
            'total' => 1620,
            'periodo' => Carbon::now()->format('Y-m'),
            'payload' => [
                'nombre' => 'Test Prepay Client',
                'prepay' => 'si',
                'prepay_months' => 6,
                'metodo' => 'Efectivo'
            ],
            'created_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard.metrics', ['period' => 'day']));

        $response->assertStatus(200);
        $response->assertJsonFragment(['numero' => '1001']);
        $response->assertJsonFragment(['nombre' => 'Test Prepay Client']);
        
        $data = $response->json();
        $this->assertNotEmpty($data['prepay_clients']);
        $this->assertEquals('1001', $data['prepay_clients'][0]['numero']);
        
        // Check dates
        $from = Carbon::now()->format('d/m/Y');
        $to = Carbon::now()->addMonths(6)->format('d/m/Y');
        $this->assertEquals($from, $data['prepay_clients'][0]['desde']);
        $this->assertEquals($to, $data['prepay_clients'][0]['hasta']);
    }

    public function test_dashboard_filters_only_prepay_clients()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Prepay invoice
        Factura::create([
            'reference_number' => 1,
            'numero_servicio' => '1001',
            'total' => 1620,
            'payload' => ['prepay' => 'si', 'prepay_months' => 6, 'nombre' => 'Client A'],
        ]);

        // Normal invoice
        Factura::create([
            'reference_number' => 2,
            'numero_servicio' => '1002',
            'total' => 300,
            'payload' => ['prepay' => 'no', 'nombre' => 'Client B'],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard.metrics'));

        $response->assertJsonCount(1, 'prepay_clients');
        $this->assertEquals('1001', $response->json('prepay_clients.0.numero'));
    }
}
