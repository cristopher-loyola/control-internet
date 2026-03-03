<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagoDuplicadoPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_primer_pago_sin_historial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $resp = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '1001',
            'usuario_id' => null,
            'total' => 300,
            'payload' => ['nombre' => 'Cliente A', 'mensualidad' => 300, 'recargo' => 'no', 'pago_anterior' => 0],
        ]);
        $resp->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('facturas', 1);
        $this->assertDatabaseHas('payment_attempts', ['status' => 'success', 'reason' => 'Factura creada']);
    }

    public function test_rechaza_pago_duplicado_por_numero_servicio_y_periodo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Primer pago
        $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '2001',
            'usuario_id' => null,
            'total' => 280,
            'payload' => ['nombre' => 'Cliente B', 'mensualidad' => 280, 'recargo' => 'no', 'pago_anterior' => 0],
        ])->assertOk();

        // Intento duplicado mismo periodo con cambios (simula nuevo cobro)
        $dup = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '2001',
            'usuario_id' => null,
            'total' => 300, // cambia total
            'payload' => ['nombre' => 'Cliente B', 'mensualidad' => 300, 'recargo' => 'no', 'pago_anterior' => 0],
        ]);
        $dup->assertStatus(409)->assertJson(['ok' => false]);

        $this->assertDatabaseHas('payment_attempts', ['status' => 'duplicate']);
    }

    public function test_rechaza_pago_duplicado_por_usuario_y_periodo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Primer pago con usuario_id
        $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => null,
            'usuario_id' => 10,
            'total' => 500,
            'payload' => ['nombre' => 'Cliente C', 'mensualidad' => 500, 'recargo' => 'no', 'pago_anterior' => 0],
        ])->assertOk();

        // Duplicado por usuario_id en mismo periodo
        $dup = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => 'ZZZ',
            'usuario_id' => 10,
            'total' => 500,
            'payload' => ['nombre' => 'Cliente C', 'mensualidad' => 500, 'recargo' => 'no', 'pago_anterior' => 0],
        ]);
        $dup->assertStatus(409)->assertJson(['ok' => false]);
        $this->assertDatabaseHas('payment_attempts', ['status' => 'duplicate']);
    }

    public function test_reuso_de_factura_no_crea_duplicado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $payload = ['nombre' => 'Cliente D', 'mensualidad' => 320, 'recargo' => 'no', 'pago_anterior' => 0];
        $first = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '3001',
            'usuario_id' => null,
            'total' => 320,
            'payload' => $payload,
        ])->assertOk()->json();

        // Mismo payload debería reutilizar (ok con reused)
        $second = $this->postJson(route('admin.pagos.facturas.store'), [
            'numero_servicio' => '3001',
            'usuario_id' => null,
            'total' => 320,
            'payload' => $payload,
        ])->assertOk()->json();

        $this->assertEquals($first['referencia'], $second['referencia']);
        $this->assertDatabaseHas('payment_attempts', ['status' => 'success', 'reason' => 'Reimpresión / reuso de factura']);
    }
}
