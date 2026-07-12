<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\Usuario;
use App\Services\FacturaService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ReconciliarJunio2026 extends Command
{
    protected $signature = 'reconciliar:junio {--apply : Ejecuta los cambios (por defecto es simulación)}';
    protected $description = 'Registra la factura de junio 2026 faltante para los clientes que pagaron junio fuera del sistema';

    private const PERIODO = '2026-06';

    public function handle(FacturaService $facturaService): int
    {
        $apply = (bool) $this->option('apply');
        $numeros = require database_path('data/reconciliacion-junio-2026.php');
        $numeros = array_values(array_unique(array_map('strval', $numeros)));

        $this->info(($apply ? 'APLICANDO' : 'SIMULACIÓN (dry-run)') . ' — reconciliación junio 2026');
        $this->info('Clientes en lista: ' . count($numeros));
        $this->newLine();

        $creadas = 0; $yaTenian = 0; $noExisten = 0; $errores = 0;

        foreach ($numeros as $num) {
            $u = Usuario::where('numero_servicio', $num)->first();
            if (! $u) { $noExisten++; $this->warn("  #$num  NO EXISTE"); continue; }

            $yaTiene = Factura::whereNull('deleted_at')
                ->where('numero_servicio', $num)
                ->where('periodo', self::PERIODO)
                ->exists();
            if ($yaTiene) { $yaTenian++; continue; }

            $mensualidad = (float) preg_replace('/[^0-9.]/', '', (string) ($u->tarifa ?? 0));
            if ($mensualidad <= 0) { $errores++; $this->warn("  #$num  tarifa inválida ($u->tarifa)"); continue; }

            if (! $apply) {
                $this->line("  #$num  crearía factura junio = \${$mensualidad}");
                $creadas++;
                continue;
            }

            try {
                $req = Request::create('/reconciliar', 'POST', [
                    'numero_servicio' => $num,
                    'total'           => $mensualidad,
                    'payload'         => [
                        'periodo_override' => self::PERIODO,
                        'label'            => 'Reconciliación junio 2026',
                        'metodo_pago'      => 'Reconciliación',
                        'metodo'           => 'Reconciliación',
                        'reconciliacion'   => 'junio-2026',
                    ],
                ]);
                $r = $facturaService->crearFactura($req);
                if (! empty($r['ok'])) {
                    $creadas++;
                    $this->line("  #$num  OK folio {$r['referencia']}  \${$mensualidad}");
                } else {
                    $errores++;
                    $this->error("  #$num  ERROR: " . ($r['message'] ?? 'desconocido'));
                }
            } catch (\Throwable $e) {
                $errores++;
                $this->error("  #$num  EXCEPCIÓN: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line('Facturas ' . ($apply ? 'creadas' : 'a crear') . ': ' . $creadas);
        $this->line('Ya tenían junio (saltadas): ' . $yaTenian);
        $this->line('No existen: ' . $noExisten);
        $this->line('Errores: ' . $errores);
        if (! $apply) {
            $this->newLine();
            $this->comment('Simulación. Para aplicar: php artisan reconciliar:junio --apply');
        }

        return $errores > 0 ? 1 : 0;
    }
}
