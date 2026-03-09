<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prepay_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('paquete'); // 300,400,500,600
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['paquete']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prepay_settings');
    }
};

