<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha contable: mes al que pertenece el dinero para efectos de reportes,
 * cuando difiere del día en que se capturó.
 *
 * Caso real: las transferencias del mes se capturan durante los primeros días
 * del mes siguiente (el estado de cuenta sale entonces), pero ese dinero
 * corresponde al mes anterior y debe aparecer en su resumen.
 *
 * NULL = usar created_at (comportamiento normal de todos los pagos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (! Schema::hasColumn('facturas', 'fecha_contable')) {
                $table->timestamp('fecha_contable')->nullable()->after('created_by');
                $table->index('fecha_contable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'fecha_contable')) {
                $table->dropIndex(['fecha_contable']);
                $table->dropColumn('fecha_contable');
            }
        });
    }
};
