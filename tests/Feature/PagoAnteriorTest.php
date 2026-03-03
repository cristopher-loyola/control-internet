<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoAnteriorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_prev_endpoint_returns_404_when_no_payments(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $resp = $this->getJson(route('admin.pagos.prev', ['numero' => '1234']));
        $resp->assertStatus(404)
            ->assertJson(['ok' => false]);
    }

    public function test_admin_prev_endpoint_returns_latest_factura(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $n = '5678';
        Factura::create([
            'reference_number' => 1,
            'numero_servicio' => $n,
            'total' => 200,
            'payload' => ['nombre' => 'Juan'],
        ]);
        // Más reciente
        Factura::create([
            'reference_number' => 2,
            'numero_servicio' => $n,
            'total' => 350.75,
            'payload' => ['nombre' => 'Juan'],
        ]);

        $resp = $this->getJson(route('admin.pagos.prev', ['numero' => $n]));
        $resp->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('data.monto', 350.75);
    }

    public function test_pagos_prev_endpoint_requires_role_and_returns_data(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $this->actingAs($user);

        $n = '9999';
        Factura::create([
            'reference_number' => 10,
            'numero_servicio' => $n,
            'total' => 123.45,
            'payload' => [],
        ]);

        $resp = $this->getJson(route('pagos.recibos.prev', ['numero' => $n]));
        $resp->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonPath('data.monto', 123.45);
    }
}

