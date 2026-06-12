<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrepayDashboardService;
use Illuminate\Support\Carbon;

echo "=== Prueba del fix de fecha de vencimiento para pagos por adelantado ===\n\n";

// Test case 1: Fecha de pago hoy (12 de junio 2026), pago de junio + 2 meses de adelanto (julio y agosto)
$fechaPago = Carbon::create(2026, 6, 12);
$mesesAdelanto = 2;

echo "Test 1: Pago el " . $fechaPago->format('d/m/Y') . " con $mesesAdelanto meses de adelanto\n";
$venceEn = PrepayDashboardService::venceAt($fechaPago, $mesesAdelanto);
echo "Fecha de vencimiento calculada: " . $venceEn->format('d/m/Y') . "\n";
echo "✅ Esperado: 01/09/2026\n";
echo "Resultado: " . ($venceEn->format('Y-m-d') === '2026-09-01' ? '✅ CORRECTO' : '❌ INCORRECTO') . "\n\n";

// Test case 2: Pago el 15 de diciembre 2026 con 3 meses de adelanto
$fechaPago2 = Carbon::create(2026, 12, 15);
$mesesAdelanto2 = 3;
echo "Test 2: Pago el " . $fechaPago2->format('d/m/Y') . " con $mesesAdelanto2 meses de adelanto\n";
$venceEn2 = PrepayDashboardService::venceAt($fechaPago2, $mesesAdelanto2);
echo "Fecha de vencimiento calculada: " . $venceEn2->format('d/m/Y') . "\n";
echo "✅ Esperado: 01/04/2027\n";
echo "Resultado: " . ($venceEn2->format('Y-m-d') === '2027-04-01' ? '✅ CORRECTO' : '❌ INCORRECTO') . "\n\n";

echo "=== Prueba finalizada ===";
