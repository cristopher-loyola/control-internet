<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHomePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_home_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(200);
        $response->assertSeeText("You're logged in!");
    }

    public function test_tecnico_home_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'tecnico']);
        $response = $this->actingAs($user)->get('/tecnico');
        $response->assertStatus(200);
        $response->assertSeeText("You're logged in!");
    }

    public function test_pagos_home_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $response = $this->actingAs($user)->get('/pagos');
        $response->assertStatus(200);
        $response->assertSeeText("You're logged in!");
    }

    public function test_contrataciones_home_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'contrataciones']);
        $response = $this->actingAs($user)->get('/contrataciones');
        $response->assertStatus(200);
        $response->assertSeeText("You're logged in!");
    }
}
