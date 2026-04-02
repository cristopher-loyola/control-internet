<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        DB::table('estatus_servicios')->updateOrInsert(
            ['nombre' => 'Baja temporal'],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        DB::table('estatus_servicios')->where('nombre', 'Baja temporal')->delete();
    }
};
