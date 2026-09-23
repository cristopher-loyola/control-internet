<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\User;
use App\Models\Usuario;
use App\Services\WhatsAppNotifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReciboServicioExistenteTest extends TestCase
{
    use RefreshDatabase;

    public static function canales(): array
    {
        return [
            ['admin', 'admin.pagos.facturas.store'],
            ['pagos', 'pagos.recibos.facturas.store'],
            ['rosalito', 'rosalito.recibos.facturas.store'],
            ['chivato', 'chivato.recibos.facturas.store'],
            ['pozo_hondo', 'pozo_hondo.recibos.facturas.store'],
        ];
    }

    private function iniciarCaja(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);
        if (! in_array($role, ['admin', 'pagos'], true)) {
            CorteCaja::create([
                'user_id' => $user->id,
                'zona' => $role,
                'fecha_inicio' => now(),
                'estado' => 'activo',
            ]);
        }
        $this->mock(WhatsAppNotifierService::class)
            ->shouldReceive('enviarNotificacionReactivacion')->andReturn(false);
    }

    #[DataProvider('canales')]
    public function test_rechaza_servicio_inexistente_sin_generar_recibo_ni_consumir_folio(string $role, string $route): void
    {
        $this->iniciarCaja($role);
        $cliente = Usuario::factory()->create(['numero_servicio' => '1001']);
        $folio = DB::table('invoice_sequences')->where('name', 'facturas')->value('current_value');

        foreach (['2', '', null] as $numero) {
            $this->postJson(route($route), [
                'numero_servicio' => $numero,
                // Un ID de cliente anterior no debe permitir otro servicio inexistente.
                'usuario_id' => $cliente->id,
                'total' => 50,
                'payload' => ['nombre' => '', 'mensualidad' => 0, 'recargo' => 'si'],
            ])->assertUnprocessable()->assertJsonValidationErrors('numero_servicio');
        }

        $this->assertDatabaseCount('facturas', 0);
        $this->assertDatabaseCount('payment_attempts', 0);
        $this->assertSame($folio, DB::table('invoice_sequences')->where('name', 'facturas')->value('current_value'));
    }

    #[DataProvider('canales')]
    public function test_permite_generar_recibo_de_un_servicio_registrado(string $role, string $route): void
    {
        $this->iniciarCaja($role);
        Usuario::factory()->create(['numero_servicio' => '1001', 'tarifa' => 300]);

        $this->postJson(route($route), [
            'numero_servicio' => '1001',
            'total' => 300,
            'payload' => ['nombre' => 'Cliente registrado', 'mensualidad' => 300, 'recargo' => 'no'],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseHas('facturas', ['numero_servicio' => '1001', 'total' => 300]);
    }
}
