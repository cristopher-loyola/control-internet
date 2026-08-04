<?php

namespace App\Console\Commands;

use App\Models\Factura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ajuste único: las transferencias de julio 2026 se capturaron el 3 de agosto
 * (los primeros días del mes siguiente, cuando sale el estado de cuenta), así
 * que cayeron en el resumen de agosto en vez del de julio.
 *
 * De aquí en adelante esto ya no hace falta: el módulo de Transferencias tiene
 * el selector "Cuenta en el resumen de" que fija la fecha contable al registrar.
 */
class CorteJulioTransferencias2026 extends Command
{
    protected $signature = 'corte:transferencias-julio2026
                            {--fecha=2026-08-03 : Día en que se capturaron}
                            {--apply : Ejecuta los cambios (por defecto es simulación)}';

    protected $description = 'Manda al corte de julio 2026 las transferencias que se capturaron el 3 de agosto';

    private const FECHA_CONTABLE = '2026-07-31 23:59:59';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $fecha = (string) $this->option('fecha');

        $query = Factura::whereNull('deleted_at')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.metodo_pago')) = 'Deposito a cuenta'")
            ->whereDate('created_at', $fecha)
            ->whereNull('fecha_contable');

        $facturas = $query->get(['id', 'reference_number', 'numero_servicio', 'periodo', 'total']);

        $this->info(($apply ? 'APLICANDO' : 'SIMULACIÓN (dry-run)') . ' — corte julio 2026');
        $this->line('Transferencias capturadas el ' . $fecha . ' sin fecha contable: ' . $facturas->count());
        $this->newLine();

        if ($facturas->isEmpty()) {
            $this->comment('No hay nada que ajustar.');

            return 0;
        }

        foreach ($facturas->groupBy('periodo') as $periodo => $grupo) {
            $this->line(sprintf(
                '   periodo %-8s  %4d facturas  $%s',
                $periodo ?: 'null',
                $grupo->count(),
                number_format((float) $grupo->sum('total'), 2)
            ));
        }
        $this->newLine();
        $this->line('Se les fijaría fecha contable: ' . self::FECHA_CONTABLE . ' (corte de julio)');
        $this->line('Total: $' . number_format((float) $facturas->sum('total'), 2));

        if (! $apply) {
            $this->newLine();
            $this->comment('Simulación. Para aplicar: php artisan corte:transferencias-julio2026 --apply');

            return 0;
        }

        $ids = $facturas->pluck('id')->all();

        DB::transaction(function () use ($ids, $facturas) {
            Factura::whereIn('id', $ids)->update(['fecha_contable' => self::FECHA_CONTABLE]);

            DB::table('audit_logs')->insert([
                'actor_user_id' => null,
                'actor_role'    => 'console',
                'actor_name'    => 'corte:transferencias-julio2026',
                'action'        => 'facturas_fecha_contable_julio2026',
                'table_name'    => 'facturas',
                'entity_type'   => Factura::class,
                'entity_id'     => (string) count($ids),
                'prev_values'   => json_encode(['fecha_contable' => null, 'ids' => $ids]),
                'new_values'    => json_encode([
                    'fecha_contable' => self::FECHA_CONTABLE,
                    'total'          => (float) $facturas->sum('total'),
                ]),
                'ip'         => null,
                'user_agent' => 'artisan-command',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->newLine();
        $this->info('=== RESUMEN ===');
        $this->line('Facturas ajustadas: ' . count($ids));
        $this->line('Ahora reportan en: julio 2026');

        return 0;
    }
}
