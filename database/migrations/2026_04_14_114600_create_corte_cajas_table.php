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
        // Tabla para sesiones de corte de caja
        Schema::create('corte_cajas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Cajero que inicia el corte
            $table->string('zona'); // pozo_hondo, chivato, etc.
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->string('estado')->default('activo'); // activo, cerrado
            $table->decimal('total_recaudado', 10, 2)->default(0);
            $table->integer('total_pagos')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['zona', 'estado']);
            $table->index('user_id');
        });

        // Agregar campo corte_caja_id a facturas para relacionar pagos con un corte
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedBigInteger('corte_caja_id')->nullable()->after('created_by');
            $table->foreign('corte_caja_id')->references('id')->on('corte_cajas')->onDelete('set null');
            $table->index('corte_caja_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['corte_caja_id']);
            $table->dropColumn('corte_caja_id');
        });

        Schema::dropIfExists('corte_cajas');
    }
};
