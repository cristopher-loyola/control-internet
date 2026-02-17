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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero_servicio')->unique();
            $table->string('nombre_cliente', 150);
            $table->string('domicilio', 255);
            $table->string('telefono', 20)->nullable();
            $table->string('paquete', 100)->nullable();
            $table->string('ip_servicio', 45)->nullable();
            $table->string('olt_ubicado', 150)->nullable();

            $table->foreignId('estado_id')->constrained('estados');
            $table->foreignId('estatus_servicio_id')->constrained('estatus_servicios');
            $table->foreignId('servicio_id')->constrained('servicios');

            $table->date('fecha_contratacion')->nullable();
            $table->unsignedBigInteger('numero_servicio_anterior')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};

