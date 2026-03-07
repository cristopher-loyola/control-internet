<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos_mora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('numero_servicio')->nullable();
            $table->string('periodo', 7); // YYYY-MM
            $table->decimal('monto', 10, 2)->default(50.00);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['numero_servicio', 'periodo']);
            $table->index(['usuario_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos_mora');
    }
};

