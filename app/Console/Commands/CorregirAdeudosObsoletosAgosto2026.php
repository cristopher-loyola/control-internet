<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirAdeudosObsoletosAgosto2026 extends Command
{
    protected $signature = 'corregir:adeudos-obsoletos-agosto2026 {--apply : Ejecuta los cambios (por defecto es simulación)}';
    protected $description = 'Limpia adeudo_monto obsoleto de clientes cuyo saldo viejo ya se saldó fuera del sistema (la nota nunca se limpió y se seguía cobrando encima de las mensualidades)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $numeros = require database_path('data/adeudos-obsoletos-agosto-2026.php');
        $numeros = array_values(array_unique(array_map('strval', $numeros)));

        $this->info(($apply ? 'APLICANDO' : 'SIMULACIÓN (dry-run)') . ' — limpieza de adeudos obsoletos agosto 2026');
        $this->info('Clientes en lista: ' . count($numeros));
        $this->newLine();

        $limpiados = 0; $yaLimpios = 0; $noExisten = 0; $errores = 0;
        $totalLiberado = 0.0;

        foreach ($numeros as $num) {
            $u = Usuario::where('numero_servicio', $num)->first();
            if (! $u) { $noExisten++; $this->warn("  #$num  NO EXISTE"); continue; }

            $prevMonto = (float) $u->adeudo_monto;
            $prevDesc = $u->adeudo_descripcion;

            if ($prevMonto <= 0) {
                $yaLimpios++;
                $this->line("  #$num  sin cambios (adeudo ya en 0)");
                continue;
            }

            if (! $apply) {
                $this->line("  #$num  limpiaría \${$prevMonto}  (\"" . ($prevDesc ?: 'sin descripción') . "\")");
                $limpiados++;
                $totalLiberado += $prevMonto;
                continue;
            }

            try {
                $u->update([
                    'adeudo_monto' => 0,
                    'adeudo_descripcion' => null,
                ]);

                DB::table('audit_logs')->insert([
                    'actor_user_id' => null,
                    'actor_role' => 'console',
                    'actor_name' => 'corregir:adeudos-obsoletos-agosto2026',
                    'action' => 'usuario_limpieza_adeudo_obsoleto',
                    'table_name' => 'usuarios',
                    'entity_type' => Usuario::class,
                    'entity_id' => (string) $u->id,
                    'prev_values' => json_encode([
                        'adeudo_monto' => $prevMonto,
                        'adeudo_descripcion' => $prevDesc,
                    ]),
                    'new_values' => json_encode([
                        'adeudo_monto' => 0,
                        'adeudo_descripcion' => null,
                    ]),
                    'ip' => null,
                    'user_agent' => 'artisan-command',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $limpiados++;
                $totalLiberado += $prevMonto;
                $this->line("  #$num  OK  liberó \${$prevMonto}");
            } catch (\Throwable $e) {
                $errores++;
                $this->error("  #$num  ERROR: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line('Adeudos ' . ($apply ? 'limpiados' : 'a limpiar') . ': ' . $limpiados);
        $this->line('Total ' . ($apply ? 'liberado' : 'a liberar') . ': $' . number_format($totalLiberado, 2));
        $this->line('Ya estaban en 0: ' . $yaLimpios);
        $this->line('No existen: ' . $noExisten);
        $this->line('Errores: ' . $errores);
        if (! $apply) {
            $this->newLine();
            $this->comment('Simulación. Para aplicar: php artisan corregir:adeudos-obsoletos-agosto2026 --apply');
        }

        return $errores > 0 ? 1 : 0;
    }
}
