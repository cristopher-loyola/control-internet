<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Estados (1: Activado, 2: Desactivado)
        $estados = [
            ['id' => 1, 'nombre' => 'Activado', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nombre' => 'Desactivado', 'created_at' => $now, 'updated_at' => $now],
        ];
        foreach ($estados as $e) {
            DB::table('estados')->updateOrInsert(['id' => $e['id']], $e);
        }

        // Estatus de servicio (1: Pagado, 2: Suspendido, 3: Cancelado, 4: Pendiente de pago, 5: Baja temporal)
        $estatus = [
            ['id' => 1, 'nombre' => 'Pagado', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'nombre' => 'Suspendido', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'nombre' => 'Cancelado', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'nombre' => 'Pendiente de pago', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'nombre' => 'Baja temporal', 'created_at' => $now, 'updated_at' => $now],
        ];
        foreach ($estatus as $s) {
            DB::table('estatus_servicios')->updateOrInsert(['id' => $s['id']], $s);
        }
    }
}
