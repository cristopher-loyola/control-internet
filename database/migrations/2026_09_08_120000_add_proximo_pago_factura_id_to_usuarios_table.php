<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            // Último recibo existente al reemplazar el saldo. No es una FK:
            // el límite debe sobrevivir incluso si ese recibo se elimina.
            $table->unsignedBigInteger('proximo_pago_factura_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('proximo_pago_factura_id');
        });
    }
};
