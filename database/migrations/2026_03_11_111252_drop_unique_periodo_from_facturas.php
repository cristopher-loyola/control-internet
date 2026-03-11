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
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique('facturas_numero_servicio_periodo_unique');
            $table->dropUnique('facturas_usuario_id_periodo_unique');
            
            // Reemplazamos por índices normales para mantener el rendimiento de búsqueda
            $table->index(['numero_servicio', 'periodo']);
            $table->index(['usuario_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex(['numero_servicio', 'periodo']);
            $table->dropIndex(['usuario_id', 'periodo']);
            
            $table->unique(['numero_servicio', 'periodo'], 'facturas_numero_servicio_periodo_unique');
            $table->unique(['usuario_id', 'periodo'], 'facturas_usuario_id_periodo_unique');
        });
    }
};
