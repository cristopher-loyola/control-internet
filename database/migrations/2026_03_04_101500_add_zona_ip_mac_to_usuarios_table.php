<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('zona', 100)->nullable()->after('comunidad');
            $table->string('ip', 45)->nullable()->after('ip_servicio');
            $table->string('mac', 20)->nullable()->after('ip');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['zona', 'ip', 'mac']);
        });
    }
};

