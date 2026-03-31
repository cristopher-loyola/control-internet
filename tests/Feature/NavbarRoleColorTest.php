<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarRoleColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_colors_configuration_has_required_roles_and_non_empty_values(): void
    {
        $colors = config('role_colors.navbar');

        $this->assertIsArray($colors);
        foreach (['admin', 'tecnico', 'pagos', 'contrataciones', 'rosalito', 'pozo_hondo', 'chivato'] as $k) {
            $this->assertArrayHasKey($k, $colors);
            $this->assertIsString($colors[$k]);
            $this->assertNotSame('', trim($colors[$k]));
        }
    }

    public function test_admin_navbar_uses_admin_color(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertSee(config('role_colors.navbar.admin'), false);
    }

    public function test_tecnico_navbar_uses_tecnico_color(): void
    {
        $user = User::factory()->create(['role' => 'tecnico']);

        $response = $this->actingAs($user)->get('/tecnico');

        $response->assertSee(config('role_colors.navbar.tecnico'), false);
    }

    public function test_pagos_navbar_uses_pagos_color(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);

        $response = $this->actingAs($user)->get('/pagos');

        $response->assertSee(config('role_colors.navbar.pagos'), false);
    }

    public function test_contrataciones_navbar_uses_contrataciones_color(): void
    {
        $user = User::factory()->create(['role' => 'contrataciones']);

        $response = $this->actingAs($user)->get('/contrataciones');

        $response->assertSee(config('role_colors.navbar.contrataciones'), false);
    }
}
