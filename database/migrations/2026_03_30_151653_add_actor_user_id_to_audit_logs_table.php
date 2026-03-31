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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_user_id')->nullable()->index()->after('id');
            $table->string('actor_role', 50)->nullable()->index()->after('actor_user_id');
            $table->string('actor_name', 150)->nullable()->after('actor_role');
            $table->string('action', 100)->index()->after('actor_name');
            $table->string('table_name', 120)->nullable()->index()->after('action');
            $table->string('entity_type', 120)->nullable()->index()->after('table_name');
            $table->string('entity_id', 120)->nullable()->index()->after('entity_type');
            $table->json('prev_values')->nullable()->after('entity_id');
            $table->json('new_values')->nullable()->after('prev_values');
            $table->string('ip', 45)->nullable()->after('new_values');
            $table->text('user_agent')->nullable()->after('ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'actor_user_id',
                'actor_role',
                'actor_name',
                'action',
                'table_name',
                'entity_type',
                'entity_id',
                'prev_values',
                'new_values',
                'ip',
                'user_agent',
            ]);
        });
    }
};
