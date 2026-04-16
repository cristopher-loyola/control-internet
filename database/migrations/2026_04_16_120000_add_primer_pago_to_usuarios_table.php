<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->decimal('primer_pago', 10, 2)->nullable()->after('tarifa');
            $table->date('primer_pago_vencimiento')->nullable()->after('primer_pago');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['primer_pago', 'primer_pago_vencimiento']);
        });
    }
};
