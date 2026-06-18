<?php

/**
 * Compara el CSV contra la base de datos actual.
 * Uso: php comparar_csv.php "C:\ruta\prueba.csv"
 *
 * Lógica de cálculo del sistema (igual a MorosidadService):
 *  - Si proximo_pago > mes actual (sin importar descripción) → total sistema = $0
 *  - Si adeudo_monto > 0                                     → total sistema = tarifa + adeudo_monto
 *  - Normal                                                   → total sistema = tarifa
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// ── Ruta del CSV ──────────────────────────────────────────────────────────────
$csvPath = $argv[1] ?? null;
if (!$csvPath || !file_exists($csvPath)) {
    echo "Uso: php comparar_csv.php \"C:\\Users\\CTRL\\Documents\\prueba.csv\"\n";
    exit(1);
}

// ── Abrir CSV ─────────────────────────────────────────────────────────────────
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
$colDesc = findCol($header, ['descripcion', 'descripción']);
$colTot  = findCol($header, ['total a pagar', 'total']);

if ($colNum === false || $colTot === false) {
    echo "ERROR: columnas no encontradas. Headers: " . implode(' | ', $header) . "\n";
    exit(1);
}

function parseMonto(mixed $v): float {
    if ($v === null || $v === '') return 0.0;
    $c = str_replace([',', '$', ' '], '', trim((string)$v));
    return is_numeric($c) ? (float)$c : 0.0;
}

// ── Mes actual para comparar proximo_pago ─────────────────────────────────────
$mesPeriodo = date('Y-m'); // e.g. 2026-06

// ── Procesar ──────────────────────────────────────────────────────────────────
$concuerdan   = 0;
$discrepancias = [];
$noEncontrados = [];
$omitidos      = 0;

while (($data = fgetcsv($handle, 0, $sep)) !== false) {
    $numero   = isset($data[$colNum]) ? trim($data[$colNum]) : '';
    $nombre   = $colNom !== false && isset($data[$colNom]) ? trim($data[$colNom]) : '';
    $csvTar   = $colTar  !== false && isset($data[$colTar])  ? parseMonto($data[$colTar])  : 0;
    $csvTotal = isset($data[$colTot]) ? parseMonto($data[$colTot]) : 0;

    if ($numero === '' || $nombre === '' || $csvTar == 0) { $omitidos++; continue; }

    $u = DB::table('usuarios')
        ->where('numero_servicio', $numero)
        ->first(['numero_servicio','nombre_cliente','tarifa','adeudo_monto',
                 'proximo_pago_monto','adeudo_descripcion','proximo_pago']);

    if (!$u) { $noEncontrados[] = ['numero'=>$numero,'nombre'=>$nombre,'csv'=>$csvTotal]; continue; }

    // Replicar lógica de MorosidadService
    $dbTarifa = (float)($u->proximo_pago_monto ?? $u->tarifa ?? 0);
    $dbAdeudo = (float)($u->adeudo_monto ?? 0);
    $proxPago = $u->proximo_pago ?? '';

    if ($dbAdeudo <= 0 && strcmp($proxPago, $mesPeriodo) > 0) {
        // Cubierto este mes (proximo_pago en el futuro) → total = $0
        $dbTotal = 0.0;
    } elseif ($dbAdeudo > 0) {
        // Tiene adeudo manual → tarifa + adeudo
        $dbTotal = $dbTarifa + $dbAdeudo;
    } else {
        // Normal → solo tarifa
        $dbTotal = $dbTarifa;
    }

    if (abs($dbTotal - $csvTotal) > 0.01) {
        $discrepancias[] = [
            'numero'    => $numero,
            'nombre'    => $nombre,
            'csv_total' => $csvTotal,
            'db_total'  => $dbTotal,
            'db_tarifa' => $dbTarifa,
            'db_adeudo' => $dbAdeudo,
            'prox_pago' => $proxPago,
            'desc'      => $u->adeudo_descripcion ?? '',
            'dif'       => $dbTotal - $csvTotal,
        ];
    } else {
        $concuerdan++;
    }
}
fclose($handle);

// ── Reporte ───────────────────────────────────────────────────────────────────
$total = $concuerdan + count($discrepancias) + count($noEncontrados);
echo "\n=== COMPARACIÓN CSV vs SISTEMA ===\n";
echo "Período de referencia: $mesPeriodo\n\n";
echo "Clientes activos procesados: $total\n";
echo "  ✓ Concuerdan:        $concuerdan\n";
echo "  ✗ Discrepancias:     " . count($discrepancias) . "\n";
echo "  ? No en DB:          " . count($noEncontrados) . "\n";
echo "  — Omitidos/vacíos:   $omitidos\n";

if (count($discrepancias) > 0) {
    echo "\n=== DISCREPANCIAS ===\n";
    printf("%-8s %-26s %9s %9s %9s %9s  %-18s  %+9s\n",
        'No.','Nombre','CSV_TOT','DB_TAR','DB_ADO','DB_TOT','Descripcion','Dif');
    echo str_repeat('-', 112) . "\n";
    foreach ($discrepancias as $d) {
        printf("%-8s %-26s %9.2f %9.2f %9.2f %9.2f  %-18s  %+9.2f\n",
            $d['numero'], mb_substr($d['nombre'],0,26),
            $d['csv_total'], $d['db_tarifa'], $d['db_adeudo'], $d['db_total'],
            mb_substr($d['desc'],0,18), $d['dif']);
    }
    $sumaDif = array_sum(array_column($discrepancias, 'dif'));
    echo str_repeat('-', 112) . "\n";
    printf("Diferencia total acumulada: %+.2f\n", $sumaDif);
}

if (count($noEncontrados) > 0) {
    echo "\n=== NO ENCONTRADOS EN DB ===\n";
    foreach ($noEncontrados as $nf) {
        printf("  #%-6s  %-30s  CSV: %.2f\n", $nf['numero'], $nf['nombre'], $nf['csv']);
    }
}
echo "\n";
