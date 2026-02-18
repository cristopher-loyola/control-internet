<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_redirects_to_admin_index(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.index', absolute: false));
    }

    public function test_tecnico_redirects_to_tecnico_index(): void
    {
        $user = User::factory()->create(['role' => 'tecnico']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();
        $response->assertRedirect(route('tecnico.index', absolute: false));
    }

    public function test_pagos_redirects_to_pagos_index(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();
        $response->assertRedirect(route('pagos.index', absolute: false));
    }

    public function test_contrataciones_redirects_to_contrataciones_index(): void
    {
        $user = User::factory()->create(['role' => 'contrataciones']);
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticated();
        $response->assertRedirect(route('contrataciones.index', absolute: false));
    }
}

