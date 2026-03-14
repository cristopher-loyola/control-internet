<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla para gestionar los nombres de quienes realizan los cortes
        Schema::create('cortadores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Seed inicial de cortadores
        $cortadores = ['Fer2', 'Sebas', 'Manem Offline', 'Alan', 'Angel', 'NO_ESTABA', 'Cristo'];
        foreach ($cortadores as $nombre) {
            DB::table('cortadores')->insert([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Agregar campos de seguimiento de cortes a la tabla usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedBigInteger('cortador_id')->nullable()->after('mac');
            $table->string('estado_corte')->nullable()->after('cortador_id'); // Cortado, Offline, Ya cortado, NO_ESTABA
            $table->timestamp('fecha_corte')->nullable()->after('estado_corte');
            
            $table->foreign('cortador_id')->references('id')->on('cortadores')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['cortador_id']);
            $table->dropColumn(['cortador_id', 'estado_corte', 'fecha_corte']);
        });
        Schema::dropIfExists('cortadores');
    }
};
