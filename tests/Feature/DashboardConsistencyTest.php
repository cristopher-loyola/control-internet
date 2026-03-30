<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_clientes_desactivados_count_consistent_with_metrics(): void
    {
        Cache::flush();
        $admin = User::factory()->create(['role' => 'admin']);

        Estado::create(['id' => 1, 'nombre' => 'Desactivado']);
        EstatusServicio::create(['id' => 1, 'nombre' => 'Cancelado']);
        Servicio::create(['id' => 1, 'nombre' => 'Internet']);

        Usuario::create([
            'numero_servicio' => 7001,
            'nombre_cliente' => 'U1',
            'domicilio' => 'Calle 1',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);
        Usuario::create([
            'numero_servicio' => 7002,
            'nombre_cliente' => 'U2',
            'domicilio' => 'Calle 2',
            'estado_id' => 1,
            'estatus_servicio_id' => 1,
            'servicio_id' => 1,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.dashboard.metrics'));
        $res->assertOk();
        $res->assertJsonPath('ok', true);
        $this->assertSame(2, (int) ($res->json('clientes_desactivados')));
    }
}
