<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MorosidadService;
use App\Models\Usuario;
use Illuminate\Support\Carbon;

$service = new MorosidadService();
$usuarios = Usuario::all();
$mesActual = now()->format('Y-m');
$diaDelMes = now()->day;

echo "=== Testing Cortes Logic - Escenario Actual ===\n";
echo "Fecha: " . now() . "\n";
echo "Mes actual: $mesActual\n";
echo "Día del mes: $diaDelMes\n";
echo "---------------------------\n";

function calcularPagadoMes($adeudo, $mesActual, $diaDelMes, $usuario) {
    $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
    $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;
    
    if ($mesesAdeudo == 0) {
        return true;
    } elseif ($mesesAdeudo == 1 && $desdePeriodo === $mesActual) {
        return true;
    } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes < 8) {
        return true;
    } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes >= 8) {
        return false;
    } else {
        return false;
    }
}

function calcularPagadoMesEscenarioFinal($adeudo, $mesActual, $diaDelMes, $usuario, $service) {
    // Escenario Final:
    // 1. Comportamiento original
    // 2. Usuarios con adeudo_monto >0, pero solo si:
    //    a. parsePeriodoFromDescripcion(adeudo_descripcion) returns a period < mesActual, OR
    //    b. we use the proximo_pago field?
    $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
    $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;
    $adeudoManual = $usuario->adeudo_monto > 0;
    
    // First check the original logic
    $originalPagado = calcularPagadoMes($adeudo, $mesActual, $diaDelMes, $usuario);
    
    if (!$originalPagado) {
        return false;
    }
    
    if (!$adeudoManual) {
        return true;
    }
    
    // Okay, now check adeudo_descripcion to see if it mentions a previous month
    $parsedPeriodo = $service->parsePeriodoFromDescripcion($usuario->adeudo_descripcion ?? '');
    if ($parsedPeriodo && $parsedPeriodo < $mesActual) {
        return false;
    }
    
    // Check proximo_pago: if proximo_pago < mesActual, then they owe previous months
    if (!empty($usuario->proximo_pago) && preg_match('/^\d{4}-\d{2}$/', $usuario->proximo_pago)) {
        if ($usuario->proximo_pago < $mesActual) {
            return false;
        }
    }
    
    return true;
}

$porCortarActual = 0;
$porCortarFinal = 0;

$ejemplos = [];

foreach ($usuarios as $u) {
    $adeudo = $service->calcularAdeudoUsuario($u->numero_servicio);
    
    if (!calcularPagadoMes($adeudo, $mesActual, $diaDelMes, $u)) $porCortarActual++;
    if (!calcularPagadoMesEscenarioFinal($adeudo, $mesActual, $diaDelMes, $u, $service)) {
        $porCortarFinal++;
        if (count($ejemplos) < 10) {
            $ejemplos[] = [
                'numero_servicio' => $u->numero_servicio,
                'nombre_cliente' => $u->nombre_cliente,
                'adeudo_monto' => $u->adeudo_monto,
                'adeudo_descripcion' => $u->adeudo_descripcion,
                'proximo_pago' => $u->proximo_pago,
            ];
        }
    }
}

echo "Escenario Actual (comportamiento original):\n";
echo "  Total usuarios por cortar: $porCortarActual\n";
echo "\n---------------------------\n";
echo "Escenario Final (adeudo_monto >0 + adeudo_descripcion/proximo_pago indica meses anteriores):\n";
echo "  Total usuarios por cortar: $porCortarFinal\n";
echo "\nEjemplos de usuarios que estarían en la lista:\n";
foreach ($ejemplos as $e) {
    echo "  - {$e['numero_servicio']}: {$e['nombre_cliente']}\n";
    echo "    adeudo_monto: {$e['adeudo_monto']}\n";
    echo "    adeudo_descripcion: " . ($e['adeudo_descripcion'] ?? 'null') . "\n";
    echo "    proximo_pago: " . ($e['proximo_pago'] ?? 'null') . "\n";
}
