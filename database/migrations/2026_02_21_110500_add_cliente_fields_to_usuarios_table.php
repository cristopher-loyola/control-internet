<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('comunidad', 100)->nullable()->after('domicilio');
            $table->string('uso', 50)->nullable()->after('comunidad');
            $table->integer('megas')->nullable()->after('uso');
            $table->decimal('tarifa', 8, 2)->nullable()->after('megas');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['comunidad', 'uso', 'megas', 'tarifa']);
        });
    }
};

