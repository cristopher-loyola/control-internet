<?php

/**
 * Para cada discrepancia (CSV dice que debe, sistema dice que no) busca si
 * existe una factura real reciente que explique por qué el adeudo se limpió.
 * Uso: php verificar_pagos.php "C:\ruta\prueba.csv"
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$csvPath = $argv[1] ?? null;
if (!$csvPath || !file_exists($csvPath)) {
    echo "Uso: php verificar_pagos.php \"C:\\ruta\\prueba.csv\"\n";
    exit(1);
}

$handle = fopen($csvPath, 'r');
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") rewind($handle);
$firstLine = fgets($handle);
rewind($handle);
$bom2 = fread($handle, 3);
if ($bom2 !== "\xEF\xBB\xBF") rewind($handle);
$sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

$rawHeader = fgetcsv($handle, 0, $sep);
$header = array_map(fn($h) => mb_strtolower(trim(ltrim($h, "\xEF\xBB\xBF")), 'UTF-8'), $rawHeader);

function findCol(array $h, array $opts): int|false {
    foreach ($opts as $o) { $i = array_search($o, $h); if ($i !== false) return $i; }
    return false;
}
$colNum  = findCol($header, ['numero de cliente', 'número de cliente']);
$colNom  = findCol($header, ['nombre', 'nombre cliente']);
$colTar  = findCol($header, ['tarifa']);
$colTot  = findCol($header, ['total a pagar', 'total']);

function parseMonto(mixed $v): float {
    if ($v === null || $v === '') return 0.0;
    $c = str_replace([',', '$', ' '], '', trim((string)$v));
    return is_numeric($c) ? (float)$c : 0.0;
}

$mesPeriodo = date('Y-m');

$confirmados = [];
$sinEvidencia = [];
$omitidos = 0;

while (($data = fgetcsv($handle, 0, $sep)) !== false) {
    $numero   = isset($data[$colNum]) ? trim($data[$colNum]) : '';
    $nombre   = $colNom !== false && isset($data[$colNom]) ? trim($data[$colNom]) : '';
    $csvTar   = $colTar  !== false && isset($data[$colTar])  ? parseMonto($data[$colTar])  : 0;
    $csvTotal = isset($data[$colTot]) ? parseMonto($data[$colTot]) : 0;

    if ($numero === '' || $nombre === '' || $csvTar == 0) { continue; }

    $u = DB::table('usuarios')->where('numero_servicio', $numero)
        ->first(['numero_servicio','nombre_cliente','tarifa','adeudo_monto','proximo_pago_monto','proximo_pago']);
    if (!$u) continue;

    $dbTarifa = (float)($u->proximo_pago_monto ?? $u->tarifa ?? 0);
    $dbAdeudo = (float)($u->adeudo_monto ?? 0);
    $proxPago = $u->proximo_pago ?? '';
    $proxPagoMonto = (float)($u->proximo_pago_monto ?? 0);

    if ($dbAdeudo <= 0 && strcmp($proxPago, $mesPeriodo) > 0) {
        $dbTotal = ($proxPagoMonto > 0) ? $proxPagoMonto : 0.0;
    } elseif ($dbAdeudo > 0) {
        $dbTotal = $dbTarifa + $dbAdeudo;
    } else {
        $dbTotal = $dbTarifa;
    }

    $dif = $dbTotal - $csvTotal;
    if (abs($dif) <= 0.01 || $dif >= 0) { continue; } // solo nos interesan los negativos (sistema < csv)

    // Buscar la factura mas reciente no eliminada, cualquier periodo
    $f = DB::table('facturas')
        ->where('numero_servicio', $numero)
        ->whereNull('deleted_at')
        ->orderByDesc('id')
        ->first(['id', 'periodo', 'total', 'created_at']);

    if ($f && (float)$f->total > 0) {
        $confirmados[] = [
            'numero' => $numero, 'nombre' => $nombre, 'dif' => $dif,
            'factura_id' => $f->id, 'periodo' => $f->periodo, 'total' => $f->total, 'fecha' => $f->created_at,
        ];
    } else {
        $sinEvidencia[] = ['numero' => $numero, 'nombre' => $nombre, 'dif' => $dif, 'csv_total' => $csvTotal, 'db_total' => $dbTotal];
    }
}
fclose($handle);

echo "\n=== VERIFICACION DE PAGOS (194 casos donde sistema muestra MENOS deuda que el CSV) ===\n\n";
echo "Confirmados con factura real de pago: " . count($confirmados) . "\n";
echo "SIN evidencia de pago (revisar):      " . count($sinEvidencia) . "\n\n";

if (count($sinEvidencia) > 0) {
    echo "=== SIN EVIDENCIA DE PAGO (posible problema real) ===\n";
    printf("%-8s %-30s %10s %10s %+10s\n", 'No.', 'Nombre', 'CSV_TOT', 'DB_TOT', 'Dif');
    echo str_repeat('-', 75) . "\n";
    foreach ($sinEvidencia as $s) {
        printf("%-8s %-30s %10.2f %10.2f %+10.2f\n", $s['numero'], mb_substr($s['nombre'], 0, 30), $s['csv_total'], $s['db_total'], $s['dif']);
    }
    echo "\n";
}

echo "=== MUESTRA DE CONFIRMADOS (primeros 15) ===\n";
printf("%-8s %-28s %-8s %-9s %10s  %s\n", 'No.', 'Nombre', 'Periodo', 'FacturaID', 'Total', 'Fecha');
echo str_repeat('-', 90) . "\n";
foreach (array_slice($confirmados, 0, 15) as $c) {
    printf("%-8s %-28s %-8s %-9s %10.2f  %s\n", $c['numero'], mb_substr($c['nombre'], 0, 28), $c['periodo'], $c['factura_id'], $c['total'], $c['fecha']);
}
echo "\n";
