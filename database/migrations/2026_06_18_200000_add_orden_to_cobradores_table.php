<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cobradores', 'orden')) {
            Schema::table('cobradores', function (Blueprint $table) {
                $table->unsignedTinyInteger('orden')->default(0)->after('nombre');
            });
        }

        // Asignar orden secuencial a los que tienen orden=0
        $cobradores = DB::table('cobradores')->orderBy('id')->get();
        foreach ($cobradores as $i => $c) {
            if ($c->orden === 0) {
                DB::table('cobradores')->where('id', $c->id)->update(['orden' => $i + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
