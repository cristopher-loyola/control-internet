<?php
/**
 * Testea lógica de cambio de mes en MorosidadService.
 * Uso: php test_meses.php [numero_servicio]
 *
 * Sin argumento: busca automáticamente clientes representativos.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MorosidadService;
use Illuminate\Support\Facades\DB;

$service = new MorosidadService();

$numeroArgumento = $argv[1] ?? null;

// ── Encontrar clientes de prueba ──────────────────────────────────────────────
if ($numeroArgumento) {
    $casos = [['tipo' => 'manual', 'numero' => $numeroArgumento]];
} else {
    // 1. Cliente con factura solo mayo (sin junio)
    $pagoMayo = DB::table('facturas')
        ->whereNull('deleted_at')
        ->where('periodo', '2026-05')
        ->whereNotIn('numero_servicio', function ($q) {
            $q->select('numero_servicio')->from('facturas')
              ->whereNull('deleted_at')
              ->where('periodo', '2026-06');
        })
        ->orderByDesc('total')
        ->value('numero_servicio');

    // 2. Cliente con facturas mayo Y junio
    $pagoMayoJunio = DB::table('facturas')
        ->whereNull('deleted_at')
        ->where('periodo', '2026-05')
        ->whereIn('numero_servicio', function ($q) {
            $q->select('numero_servicio')->from('facturas')
              ->whereNull('deleted_at')
              ->where('periodo', '2026-06');
        })
        ->value('numero_servicio');

    // 3. Cliente cubierto por proximo_pago (importado Excel, sin facturas)
    $cubierto = DB::table('usuarios')
        ->whereNotNull('proximo_pago')
        ->where('proximo_pago', '>', '2026-06')
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))->from('facturas')
              ->whereNull('deleted_at')
              ->whereColumn('numero_servicio', 'usuarios.numero_servicio');
        })
        ->value('numero_servicio');

    $casos = array_filter([
        $pagoMayo      ? ['tipo' => 'Pagó mayo, NO junio', 'numero' => $pagoMayo] : null,
        $pagoMayoJunio ? ['tipo' => 'Pagó mayo Y junio',   'numero' => $pagoMayoJunio] : null,
        $cubierto      ? ['tipo' => 'Cubierto por Excel (sin facturas)', 'numero' => $cubierto] : null,
    ]);
}

// ── Periodos a simular ────────────────────────────────────────────────────────
$periodos = ['2026-05', '2026-06', '2026-07'];

// ── Correr pruebas ────────────────────────────────────────────────────────────
foreach ($casos as $caso) {
    $numero = $caso['numero'];
    $tipo   = $caso['tipo'] ?? 'manual';
    $u = DB::table('usuarios')->where('numero_servicio', $numero)
           ->first(['nombre_cliente','tarifa','proximo_pago','adeudo_descripcion','adeudo_monto']);

    echo "\n" . str_repeat('═', 72) . "\n";
    echo "CLIENTE #{$numero} — {$u->nombre_cliente}\n";
    echo "Tipo prueba  : {$tipo}\n";
    echo "Tarifa       : \${$u->tarifa}";
    if ($u->proximo_pago)        echo " | proximo_pago: {$u->proximo_pago}";
    if ($u->adeudo_descripcion)  echo " | desc: {$u->adeudo_descripcion}";
    echo "\n";
    echo str_repeat('─', 72) . "\n";
    printf("%-10s %8s %6s %8s %8s  %-6s  %s\n",
        'Periodo','Mensual','Meses','Recargo','Pendiente','Cubier','Descripcion');
    echo str_repeat('─', 72) . "\n";

    foreach ($periodos as $p) {
        $r = $service->calcularAdeudoUsuario($numero, $p);
        if (!$r['ok']) {
            printf("%-10s  ERROR: %s\n", $p, $r['message']);
            continue;
        }
        $cubier = $r['cubierto_este_mes'] ? 'SI' : 'no';
        $desc   = mb_substr($r['descripcion_manual'] ?? '', 0, 22);
        printf("%-10s %8.2f %6d %8.2f %8.2f  %-6s  %s\n",
            $p,
            $r['mensualidad'],
            $r['meses_adeudo'],
            $r['recargo'],
            $r['pendiente'],
            $cubier,
            $desc
        );
    }
}

// ── Facturas del cliente ──────────────────────────────────────────────────────
foreach ($casos as $caso) {
    $numero = $caso['numero'];
    $facturas = DB::table('facturas')
        ->whereNull('deleted_at')
        ->where('numero_servicio', $numero)
        ->orderByDesc('periodo')
        ->limit(6)
        ->get(['periodo','total','created_at']);

    if ($facturas->isEmpty()) {
        echo "\n  #{$numero} — sin facturas en DB\n";
        continue;
    }
    echo "\n  Facturas recientes #{$numero}:\n";
    foreach ($facturas as $f) {
        printf("    %s  \$%.2f  (%s)\n", $f->periodo, $f->total, substr($f->created_at,0,10));
    }
}
echo "\n";
