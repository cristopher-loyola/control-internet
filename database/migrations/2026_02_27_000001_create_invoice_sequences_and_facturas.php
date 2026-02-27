<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->timestamps();
        });

        DB::table('invoice_sequences')->insert([
            'name' => 'facturas',
            'current_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reference_number')->unique();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('numero_servicio')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('invoice_sequences');
    }
};

