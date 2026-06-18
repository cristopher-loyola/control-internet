<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobradores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Migrar los 5 cobradores actualmente hardcodeados en las vistas
        DB::table('cobradores')->insert([
            ['nombre' => 'Luz',        'orden' => 1, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Jaime',      'orden' => 2, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Nancy',      'orden' => 3, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Alan',       'orden' => 4, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Cristopher', 'orden' => 5, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cobradores');
    }
};
