<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimpresionFechaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_factura_show_includes_created_at_field()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $f = Factura::create([
            'reference_number' => 12345,
            'numero_servicio' => '7777',
            'total' => 100.00,
            'payload' => ['nombre' => 'Cliente A'],
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $resp = $this->getJson(route('admin.pagos.facturas.show', ['id' => $f->id]));
        $resp->assertOk()
            ->assertJsonPath('data.id', $f->id)
            ->assertJsonPath('data.created_at', $f->created_at->toJSON());
    }

    public function test_pagos_factura_show_includes_created_at_field()
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $this->actingAs($user);

        $f = Factura::create([
            'reference_number' => 22222,
            'numero_servicio' => '9999',
            'total' => 150.00,
            'payload' => ['nombre' => 'Cliente B'],
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $resp = $this->getJson(route('pagos.recibos.facturas.show', ['id' => $f->id]));
        $resp->assertOk()
            ->assertJsonPath('data.id', $f->id)
            ->assertJsonPath('data.created_at', $f->created_at->toJSON());
    }
}

