<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturaFolioControlTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($user);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Cliente X',
            'mensualidad' => 400.00,
            'recargo' => 'no',
            'pago_anterior' => 0,
            'metodo' => 'Efectivo',
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
        ], $overrides);
    }

    public function test_reuses_same_folio_on_subsequent_exports_with_same_data(): void
    {
        $this->actingAdmin();

        $first = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '1234',
            'total' => 450.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $second = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '1234',
            'total' => 450.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $this->assertSame($first, $second);
    }

    public function test_reuses_original_folio_after_soft_delete(): void
    {
        $this->actingAdmin();

        $ref = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '9999',
            'total' => 250.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $f = Factura::where('reference_number', $ref)->firstOrFail();
        $f->delete();

        $ref2 = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '9999',
            'total' => 250.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $this->assertSame($ref, $ref2);
    }

    public function test_generates_new_folio_when_data_changes(): void
    {
        $this->actingAdmin();

        $r1 = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '5678',
            'total' => 300.00,
            'payload' => $this->payload(),
        ])->assertOk()->json('referencia');

        $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '5678',
            'total' => 350.00,
            'payload' => $this->payload(['mensualidad' => 350.00]),
        ])->assertStatus(409);
    }
}
