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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->text('adeudo_descripcion')->nullable()->after('estado_corte');
            $table->decimal('adeudo_monto', 10, 2)->default(0.00)->after('adeudo_descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['adeudo_descripcion', 'adeudo_monto']);
        });
    }
};
