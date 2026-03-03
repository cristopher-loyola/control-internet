<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistorialCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($user);
    }

    protected function actingPagos()
    {
        $user = User::factory()->create(['role' => 'pagos']);
        return $this->actingAs($user);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Cliente Z',
            'mensualidad' => 500.00,
            'recargo' => 'no',
            'pago_anterior' => 0,
            'metodo' => 'Efectivo',
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
        ], $overrides);
    }

    public function test_admin_can_cancel_receipt_and_sets_soft_delete(): void
    {
        $this->actingAdmin();
        $id = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '1111',
            'total' => 500.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('id');

        $this->post(route('admin.pagos.facturas.cancel', ['id' => $id]), [
            'motivo' => 'Error en captura',
        ])->assertRedirect();

        $f = Factura::withTrashed()->findOrFail($id);
        $this->assertTrue($f->trashed());
    }

    public function test_only_admin_can_cancel_receipt(): void
    {
        $this->actingAdmin();
        $id = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '2222',
            'total' => 410.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('id');
        auth()->logout();

        $this->actingPagos();
        $this->post(route('admin.pagos.facturas.cancel', ['id' => $id]), [
            'motivo' => 'Prueba',
        ])->assertStatus(403);
    }

    public function test_print_flow_unaffected_after_cancellation(): void
    {
        $this->actingAdmin();
        $referencia = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '3333',
            'total' => 600.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $f = Factura::where('reference_number', $referencia)->firstOrFail();
        $f->delete();

        $referencia2 = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '3333',
            'total' => 600.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $this->assertSame($referencia, $referencia2);
    }
}

