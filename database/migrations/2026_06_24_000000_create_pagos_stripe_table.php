<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_stripe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('payment_intent_id')->unique();
            $table->decimal('monto', 10, 2);
            $table->string('estado')->default('pendiente'); // pendiente | completado | fallido
            $table->string('periodo', 7)->nullable();       // YYYY-MM
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_stripe');
    }
};
