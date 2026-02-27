<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturaSequenceTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAdmin()
    {
        $user = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($user);
    }

    public function test_sequence_generates_consecutive_numbers(): void
    {
        $this->actingAdmin();

        $r1 = $this->postJson(route('admin.pagos.facturas.store'), ['total' => 10.00])->assertOk()->json('referencia');
        $r2 = $this->postJson(route('admin.pagos.facturas.store'), ['total' => 11.00])->assertOk()->json('referencia');
        $r3 = $this->postJson(route('admin.pagos.facturas.store'), ['total' => 12.00])->assertOk()->json('referencia');

        $this->assertSame(1, $r1);
        $this->assertSame(2, $r2);
        $this->assertSame(3, $r3);
    }

    public function test_sequence_is_unique_and_transactions_prevent_duplicates(): void
    {
        $this->actingAdmin();

        $ref = $this->postJson(route('admin.pagos.facturas.store'), ['total' => 10.00])->assertOk()->json('referencia');

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('facturas')->insert([
            'reference_number' => $ref,
            'total' => 10.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_view_contains_reference_placeholder(): void
    {
        $this->actingAdmin();
        $html = $this->get(route('admin.pagos.index', absolute: false))->assertOk()->getContent();
        $this->assertStringContainsString('class="ref-number"', $html);
    }
}
