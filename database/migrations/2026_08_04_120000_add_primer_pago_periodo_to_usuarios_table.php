<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mes del primer pago (YYYY-MM).
 *
 * Un cliente puede darse de alta hoy y quedar de pagar hasta un mes posterior,
 * con un monto distinto al de su mensualidad (promoción, prorrateo, etc.).
 *
 * Ej: alta en agosto, primer pago octubre $15, paquete $500
 *   agosto y septiembre  -> cubiertos, no debe nada
 *   octubre              -> debe $15 (primer_pago)
 *   noviembre en adelante-> debe $500 (tarifa normal)
 *
 * NULL = cliente sin trato especial: se le cobra su tarifa desde el inicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'primer_pago_periodo')) {
                $table->string('primer_pago_periodo', 7)->nullable()->after('primer_pago');
                $table->index('primer_pago_periodo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'primer_pago_periodo')) {
                $table->dropIndex(['primer_pago_periodo']);
                $table->dropColumn('primer_pago_periodo');
            }
        });
    }
};
