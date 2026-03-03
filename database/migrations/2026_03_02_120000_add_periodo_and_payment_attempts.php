<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'periodo')) {
                $table->string('periodo', 7)->nullable()->after('numero_servicio'); // YYYY-MM
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->unique(['numero_servicio', 'periodo'], 'facturas_numero_servicio_periodo_unique');
            $table->unique(['usuario_id', 'periodo'], 'facturas_usuario_id_periodo_unique');
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('numero_servicio')->nullable();
            $table->string('periodo', 7)->nullable();
            $table->string('status', 20); // success | duplicate | error
            $table->string('reason')->nullable(); // mensaje o código
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();
            $table->index(['numero_servicio', 'periodo']);
            $table->index(['usuario_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            if (Schema::hasColumn('facturas', 'periodo')) {
                $table->dropUnique('facturas_numero_servicio_periodo_unique');
                $table->dropUnique('facturas_usuario_id_periodo_unique');
                $table->dropColumn('periodo');
            }
        });
        Schema::dropIfExists('payment_attempts');
    }
};
