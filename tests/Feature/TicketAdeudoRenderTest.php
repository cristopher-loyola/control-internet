<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAdeudoRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pagos_view_incluye_campos_de_adeudo_en_ticket_y_recibo(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.pagos.index', absolute: false));

        $response->assertOk();
        $response->assertSee('Adeudo pendiente', escape: false);
        $response->assertSee('Saldo pendiente', escape: false);
        $response->assertSee('Total adelanto', escape: false);
    }

    public function test_pagos_recibos_view_incluye_campos_de_adeudo_en_ticket_y_recibo(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);

        $response = $this->actingAs($user)->get(route('pagos.recibos', absolute: false));

        $response->assertOk();
        $response->assertSee('Adeudo pendiente', escape: false);
        $response->assertSee('Saldo pendiente', escape: false);
        $response->assertSee('Total adelanto', escape: false);
    }
}

