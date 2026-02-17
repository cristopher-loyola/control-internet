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
        Schema::create('instalaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->date('fecha_contratacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('coordenadas')->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();
        });

        Schema::create('movimientos_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->foreignId('tipo_pago_id')->constrained('tipo_pagos');
            $table->foreignId('cajero_id')->nullable()->constrained('cajeros');
            $table->timestamps();
        });

        Schema::create('cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cambios');
        Schema::dropIfExists('movimientos_pagos');
        Schema::dropIfExists('instalaciones');
    }
};

