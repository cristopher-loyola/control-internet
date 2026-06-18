<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

// Añadir columna orden si no existe
if (!Schema::hasColumn('cobradores', 'orden')) {
    Schema::table('cobradores', function (Blueprint $table) {
        $table->unsignedTinyInteger('orden')->default(0)->after('nombre');
    });
    echo "Columna 'orden' añadida.\n";
} else {
    echo "Columna 'orden' ya existe.\n";
}

// Asignar orden secuencial
$cobradores = DB::table('cobradores')->orderBy('id')->get();
foreach ($cobradores as $i => $c) {
    DB::table('cobradores')->where('id', $c->id)->update(['orden' => $i + 1]);
}
echo "Orden asignado a " . count($cobradores) . " cobradores.\n";

// Marcar migración como ejecutada
$existe = DB::table('migrations')->where('migration', '2026_06_18_123028_create_cobradores_table')->exists();
if (!$existe) {
    $batch = DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert(['migration' => '2026_06_18_123028_create_cobradores_table', 'batch' => $batch]);
    echo "Migración marcada como ejecutada (batch $batch).\n";
} else {
    echo "Migración ya marcada.\n";
}

echo "Done.\n";
