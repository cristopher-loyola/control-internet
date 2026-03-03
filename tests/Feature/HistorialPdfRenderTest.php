<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistorialPdfRenderTest extends TestCase
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

    public function test_admin_historial_pdf_view_renders_table(): void
    {
        $this->actingAdmin();
        $resp = $this->get(route('admin.pagos.historial.export', ['format' => 'pdf']));
        $resp->assertOk();
        $resp->assertSee('Folio');
    }

    public function test_pagos_historial_pdf_view_renders_table(): void
    {
        $this->actingPagos();
        $resp = $this->get(route('pagos.recibos.historial.export', ['format' => 'pdf']));
        $resp->assertOk();
        $resp->assertSee('Folio');
    }
}

