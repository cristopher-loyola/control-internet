<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\EstatusServicio;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CanceladosEstadoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'Activado', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nombre' => 'Desactivado', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('estatus_servicios')->insert([
            ['id' => 3, 'nombre' => 'Cancelado', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('servicios')->insert([
            ['id' => 1, 'nombre' => 'Internet Residencial', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_admin_puede_actualizar_estado_en_cancelados(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u = Usuario::create([
            'numero_servicio' => '9100',
            'nombre_cliente' => 'Cancelado',
            'domicilio' => '-',
            'estado_id' => 2,
            'estatus_servicio_id' => 3,
            'servicio_id' => 1,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.dashboard.cancelados.estado', $u->id), ['estado_id' => 1])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $u->refresh();
        $this->assertSame(1, (int) $u->estado_id);
    }
}
