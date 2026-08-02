<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirBajaTemporalAgosto2026 extends Command
{
    protected $signature = 'corregir:baja-temporal-agosto2026 {--apply : Ejecuta los cambios (por defecto es simulación)}';
    protected $description = 'Corrige proximo_pago de clientes con baja temporal/suspensión capturada solo con nota de texto, sin dejar rastro estructurado de cuándo termina la pausa';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $mapa = require database_path('data/baja-temporal-agosto-2026.php');

        $this->info(($apply ? 'APLICANDO' : 'SIMULACIÓN (dry-run)') . ' — corrección baja temporal agosto 2026');
        $this->info('Clientes en lista: ' . count($mapa));
        $this->newLine();

        $corregidos = 0; $noExisten = 0; $errores = 0;

        foreach ($mapa as $num => $nuevoProximoPago) {
            $u = Usuario::where('numero_servicio', (string) $num)->first();
            if (! $u) { $noExisten++; $this->warn("  #$num  NO EXISTE"); continue; }

            $prevProximoPago = $u->proximo_pago;
            if ($prevProximoPago === $nuevoProximoPago) {
                $this->line("  #$num  sin cambios (ya correcto)");
                continue;
            }
            // Nunca retroceder: si el cliente ya tiene más cobertura que la
            // propuesta (ej. pagó normal después de la pausa), no se le quita.
            if ($prevProximoPago && strcmp($prevProximoPago, $nuevoProximoPago) > 0) {
                $this->line("  #$num  se salta: ya tiene mejor cobertura ($prevProximoPago > $nuevoProximoPago)");
                continue;
            }

            if (! $apply) {
                $this->line("  #$num  proximo_pago: " . ($prevProximoPago ?: 'null') . " -> $nuevoProximoPago");
                $corregidos++;
                continue;
            }

            try {
                $u->update(['proximo_pago' => $nuevoProximoPago]);

                DB::table('audit_logs')->insert([
                    'actor_user_id' => null,
                    'actor_role' => 'console',
                    'actor_name' => 'corregir:baja-temporal-agosto2026',
                    'action' => 'usuario_correccion_baja_temporal',
                    'table_name' => 'usuarios',
                    'entity_type' => Usuario::class,
                    'entity_id' => (string) $u->id,
                    'prev_values' => json_encode(['proximo_pago' => $prevProximoPago]),
                    'new_values' => json_encode(['proximo_pago' => $nuevoProximoPago]),
                    'ip' => null,
                    'user_agent' => 'artisan-command',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $corregidos++;
                $this->line("  #$num  OK -> proximo_pago=$nuevoProximoPago");
            } catch (\Throwable $e) {
                $errores++;
                $this->error("  #$num  ERROR: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line('Corregidos ' . ($apply ? '' : '(a corregir) ') . ': ' . $corregidos);
        $this->line('No existen: ' . $noExisten);
        $this->line('Errores: ' . $errores);
        if (! $apply) {
            $this->newLine();
            $this->comment('Simulación. Para aplicar: php artisan corregir:baja-temporal-agosto2026 --apply');
        }

        return $errores > 0 ? 1 : 0;
    }
}
