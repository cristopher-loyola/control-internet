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
        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });

        Schema::create('estatus_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });

        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });

        Schema::create('tipo_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100);
            $table->timestamps();
        });

        Schema::create('perfiles_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100);
            $table->timestamps();
        });

        Schema::create('cajeros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('perfil_usuario_id')->constrained('perfiles_usuarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajeros');
        Schema::dropIfExists('perfiles_usuarios');
        Schema::dropIfExists('tipo_pagos');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('estatus_servicios');
        Schema::dropIfExists('estados');
    }
};

