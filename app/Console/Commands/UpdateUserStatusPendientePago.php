<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Usuario;
use App\Models\Factura;
use Illuminate\Support\Carbon;

class UpdateUserStatusPendientePago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-status-pendiente-pago';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estatus de los usuarios a Pendiente de pago si no han pagado después del día 7';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoy = Carbon::now();
        $dia = $hoy->day;

        if ($dia < 8) {
            $this->info('Hoy es día ' . $dia . '. El cambio automático solo se realiza después del día 7.');
            return;
        }

        $periodoActual = $hoy->format('Y-m');

        // Usuarios que están "Pagado" (ID 1) o sin estatus, pero no han pagado este mes
        $usuarios = Usuario::where(function($q) {
                $q->where('estatus_servicio_id', 1)
                  ->orWhereNull('estatus_servicio_id');
            })
            ->get();

        $count = 0;
        foreach ($usuarios as $usuario) {
            $pagado = Factura::where('numero_servicio', $usuario->numero_servicio)
                ->where('periodo', $periodoActual)
                ->whereNull('deleted_at')
                ->exists();

            if (!$pagado) {
                $usuario->update(['estatus_servicio_id' => 4]);
                $count++;
            }
        }

        $this->info('Se actualizaron ' . $count . ' usuarios a "Pendiente de pago".');
    }
}
