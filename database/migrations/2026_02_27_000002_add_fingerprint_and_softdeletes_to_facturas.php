<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'fingerprint')) {
                $table->string('fingerprint', 64)->nullable()->unique()->after('created_by');
            }
            if (!Schema::hasColumn('facturas', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'fingerprint')) {
                $table->dropUnique(['fingerprint']);
                $table->dropColumn('fingerprint');
            }
            if (Schema::hasColumn('facturas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

