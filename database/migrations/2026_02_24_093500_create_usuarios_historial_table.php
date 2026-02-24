<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_original_id')->nullable();
            $table->string('accion', 20); // create, update, delete
            $table->timestamp('captured_at')->useCurrent();

            $table->unsignedBigInteger('numero_servicio')->nullable();
            $table->string('nombre_cliente', 150)->nullable();
            $table->string('domicilio', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('comunidad', 100)->nullable();
            $table->string('uso', 50)->nullable();
            $table->string('tecnologia', 10)->nullable();
            $table->string('dispositivo', 100)->nullable();
            $table->integer('megas')->nullable();
            $table->decimal('tarifa', 8, 2)->nullable();
            $table->string('paquete', 100)->nullable();
            $table->foreignId('estado_id')->nullable()->constrained('estados');
            $table->foreignId('estatus_servicio_id')->nullable()->constrained('estatus_servicios');
            $table->foreignId('servicio_id')->nullable()->constrained('servicios');
            $table->date('fecha_contratacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_historial');
    }
};

