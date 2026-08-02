<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirAdelantosAgosto2026 extends Command
{
    protected $signature = 'corregir:adelantos-agosto2026 {--apply : Ejecuta los cambios (por defecto es simulación)}';
    protected $description = 'Corrige proximo_pago de clientes que pagaron por adelantado pero se capturó con Modificar total (o no se capturó), sin dejar rastro estructurado de la cobertura';

    /** numero_servicio => adeudo_monto/adeudo_descripcion son residuales de la
     * MISMA nota de adelanto (o quedaron obsoletos por el pago adelantado
     * posterior) y deben limpiarse junto con proximo_pago. */
    private const LIMPIAR_ADEUDO_TAMBIEN = ['6073', '6509', '7170'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $mapa = require database_path('data/adelantos-agosto-2026.php');

        $this->info(($apply ? 'APLICANDO' : 'SIMULACIÓN (dry-run)') . ' — corrección adelantos agosto 2026');
        $this->info('Clientes en lista: ' . count($mapa));
        $this->newLine();

        $corregidos = 0; $noExisten = 0; $errores = 0;

        foreach ($mapa as $num => $nuevoProximoPago) {
            $u = Usuario::where('numero_servicio', (string) $num)->first();
            if (! $u) { $noExisten++; $this->warn("  #$num  NO EXISTE"); continue; }

            $prevProximoPago = $u->proximo_pago;
            $prevAdeudoMonto = $u->adeudo_monto;
            $prevAdeudoDesc = $u->adeudo_descripcion;
            $limpiarAdeudo = in_array((string) $num, self::LIMPIAR_ADEUDO_TAMBIEN, true);

            $cambios = [];
            if ($prevProximoPago !== $nuevoProximoPago) {
                $cambios['proximo_pago'] = $nuevoProximoPago;
            }
            if ($limpiarAdeudo && (float) $prevAdeudoMonto > 0) {
                $cambios['adeudo_monto'] = 0;
                $cambios['adeudo_descripcion'] = null;
            }

            if (empty($cambios)) {
                $this->line("  #$num  sin cambios (ya correcto)");
                continue;
            }

            if (! $apply) {
                $this->line("  #$num  proximo_pago: " . ($prevProximoPago ?: 'null') . " -> $nuevoProximoPago" . ($limpiarAdeudo ? ' | limpia adeudo_monto/descripcion' : ''));
                $corregidos++;
                continue;
            }

            try {
                $u->update($cambios);

                DB::table('audit_logs')->insert([
                    'actor_user_id' => null,
                    'actor_role' => 'console',
                    'actor_name' => 'corregir:adelantos-agosto2026',
                    'action' => 'usuario_correccion_adelanto',
                    'table_name' => 'usuarios',
                    'entity_type' => Usuario::class,
                    'entity_id' => (string) $u->id,
                    'prev_values' => json_encode([
                        'proximo_pago' => $prevProximoPago,
                        'adeudo_monto' => $prevAdeudoMonto,
                        'adeudo_descripcion' => $prevAdeudoDesc,
                    ]),
                    'new_values' => json_encode($cambios),
                    'ip' => null,
                    'user_agent' => 'artisan-command',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $corregidos++;
                $this->line("  #$num  OK -> proximo_pago=$nuevoProximoPago" . ($limpiarAdeudo ? ' (adeudo limpiado)' : ''));
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
            $this->comment('Simulación. Para aplicar: php artisan corregir:adelantos-agosto2026 --apply');
        }

        return $errores > 0 ? 1 : 0;
    }
}
