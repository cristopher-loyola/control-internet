<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Factura;

class UpdateMissingDiscounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'descuentos:update-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update missing discount fields in existing facturas based on total vs mensualidad + recargos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando facturas con descuentos faltantes...');
        
        $facturas = Factura::whereNotNull('payload')->get();
        $updated = 0;
        
        foreach ($facturas as $factura) {
            $payload = $factura->payload;
            
            // Skip if already has discount
            if (isset($payload['descuento'])) {
                continue;
            }
            
            // Calculate expected total without discount
            $mensualidad = $payload['mensualidad'] ?? 0;
            $recargo = ($payload['recargo'] ?? 'no') === 'si' ? 50 : 0;
            $pagoAnterior = $payload['pago_anterior'] ?? 0;
            $prepayTotal = $payload['prepay_total'] ?? 0;
            
            // Determine base amount
            if ($prepayTotal > 0) {
                $expectedTotal = $prepayTotal;
            } else {
                $expectedTotal = $mensualidad + $recargo + $pagoAnterior;
            }
            
            // Calculate discount
            $actualTotal = $factura->total;
            $discount = $expectedTotal - $actualTotal;
            
            // If there's a discount (positive difference), add it to payload
            if ($discount > 0) {
                $payload['descuento'] = $discount;
                $factura->payload = $payload;
                $factura->save();
                
                $this->line("Factura #{$factura->reference_number}: Descuento de \${$discount} agregado");
                $updated++;
            }
        }
        
        $this->info("Proceso completado. Se actualizaron {$updated} facturas.");
        return 0;
    }
}
