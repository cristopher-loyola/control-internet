<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCorteDetalleTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_prints_only_cut_payments_in_numeric_service_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'pozo_hondo']);
        $corte = CorteCaja::create([
            'user_id' => $cashier->id, 'zona' => 'pozo_hondo',
            'fecha_inicio' => now()->subDay(), 'fecha_fin' => now(), 'estado' => 'cerrado',
        ]);
        foreach (['100', '2', '10', '999'] as $service) {
            DB::table('facturas')->insert([
                'reference_number' => 'F'.$service, 'numero_servicio' => $service,
                'total' => 350, 'payload' => json_encode(['nombre' => '<Cliente>', 'recargo' => 'si']),
                'corte_caja_id' => $corte->id, 'created_at' => now(), 'updated_at' => now(),
                'deleted_at' => $service === '999' ? now() : null,
            ]);
        }
        DB::table('facturas')->insert([
            'reference_number' => 'OTHER', 'numero_servicio' => '888', 'total' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.pagos.cortes.detalle-ticket', $corte->id))
            ->assertOk()->assertSeeInOrder(['Servicio 2<', 'Servicio 10<', 'Servicio 100<'], false)
            ->assertDontSee('Servicio 999')->assertDontSee('Servicio 888')
            ->assertSee('&lt;Cliente&gt;', false)->assertSee('$900.00')->assertSee('$870.00');
        $this->get(route('pagos.pozo-hondo.cortes'))->assertOk()
            ->assertSee('Imprimir detalle de pagos')
            ->assertSee(route('admin.pagos.cortes.detalle-ticket', $corte->id), false);
    }

    public function test_ticket_requires_admin_and_missing_cut_returns_404(): void
    {
        $url = route('admin.pagos.cortes.detalle-ticket', 99999);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'pozo_hondo']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertNotFound();
    }
}
