<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_transacciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('tipo', 30);
            $table->timestamps();
        });

        Schema::create('gastos_operativos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_transacciones');
            $table->foreignId('tipo_pago_id')->nullable()->constrained('tipo_pagos');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_transacciones');
            $table->foreignId('tipo_pago_id')->nullable()->constrained('tipo_pagos');
            $table->string('proveedor', 150)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->unsignedBigInteger('factura_id')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->timestamps();
            $table->index('factura_id');
        });

        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->string('producto', 150);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('minimo')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
        Schema::dropIfExists('devoluciones');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('gastos_operativos');
        Schema::dropIfExists('categorias_transacciones');
    }
};

