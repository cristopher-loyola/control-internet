<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRouteDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_is_redirected_from_dashboard_to_admin_home(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('admin.index'));
    }

    public function test_pagos_is_redirected_from_dashboard_to_pagos_home(): void
    {
        $user = User::factory()->create(['role' => 'pagos']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('pagos.index'));
    }

    public function test_tecnico_is_redirected_from_dashboard_to_tecnico_home(): void
    {
        $user = User::factory()->create(['role' => 'tecnico']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('tecnico.index'));
    }

    public function test_contrataciones_is_redirected_from_dashboard_to_contrataciones_home(): void
    {
        $user = User::factory()->create(['role' => 'contrataciones']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('contrataciones.index'));
    }

    public function test_dashboard_metrics_clientes_nuevos_usa_fecha_contratacion_cuando_existe(): void
    {
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 3, 30, 10, 0, 0, 'UTC'));
        \Illuminate\Support\Facades\Cache::flush();

        $admin = User::factory()->create(['role' => 'admin']);

        \App\Models\Estado::create(['id' => 1, 'nombre' => 'Activado']);
        \App\Models\EstatusServicio::create(['id' => 1, 'nombre' => 'Activo']);
        \App\Models\Servicio::create(['id' => 1, 'nombre' => 'Internet']);

        $u = \App\Models\Usuario::create([
            'numero_servicio' => 9001,
            'nombre_cliente' => 'Cliente Antiguo',
            'domicilio' => 'Calle 1',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
            'fecha_contratacion' => '2026-03-30',
        ]);
        \Illuminate\Support\Facades\DB::table('usuarios')
            ->where('id', $u->id)
            ->update([
                'created_at' => now()->copy()->subYears(2),
                'updated_at' => now()->copy()->subYears(2),
            ]);

        $res = $this->actingAs($admin)->get(route('admin.dashboard.metrics'));
        $res->assertOk();
        $res->assertJsonPath('ok', true);
        $res->assertJsonPath('clientes_nuevos.day', 1);
    }
}
