<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonaDashboardsResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_rosalito_dashboard_uses_dark_theme_and_responsive_classes(): void
    {
        $user = User::factory()->create(['role' => 'rosalito']);
        $res = $this->actingAs($user)->get('/rosalito');
        $res->assertOk();
        $res->assertSee('bg-black', false);
        $res->assertSee('sm:px-6', false);
        $res->assertSee('lg:px-8', false);
        $res->assertSee('sm:grid-cols-2', false);
        $res->assertSee('xl:grid-cols-4', false);
        $res->assertSee('lg:grid-cols-5', false);
        $res->assertSee('images/Clogo.png', false);
    }

    public function test_pozo_hondo_dashboard_uses_dark_theme_and_responsive_classes(): void
    {
        $user = User::factory()->create(['role' => 'pozo_hondo']);
        $res = $this->actingAs($user)->get('/pozo-hondo');
        $res->assertOk();
        $res->assertSee('bg-black', false);
        $res->assertSee('sm:grid-cols-2', false);
        $res->assertSee('xl:grid-cols-4', false);
        $res->assertSee('images/Clogo.png', false);
    }

    public function test_chivato_dashboard_uses_dark_theme_and_responsive_classes(): void
    {
        $user = User::factory()->create(['role' => 'chivato']);
        $res = $this->actingAs($user)->get('/chivato');
        $res->assertOk();
        $res->assertSee('bg-black', false);
        $res->assertSee('lg:grid-cols-5', false);
        $res->assertSee('images/Clogo.png', false);
    }
}
