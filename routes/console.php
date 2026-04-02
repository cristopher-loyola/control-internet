<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('usuarios:cleanup-importado {--dry-run : Solo muestra los registros sin eliminarlos}', function () {
    $base = \App\Models\Usuario::query()->whereRaw('LOWER(nombre_cliente) = ?', ['importado']);
    $rows = (clone $base)
        ->orderBy('numero_servicio')
        ->get(['id', 'numero_servicio', 'nombre_cliente']);

    $this->info('Encontrados: '.$rows->count());
    foreach ($rows as $r) {
        $this->line($r->numero_servicio.' (id '.$r->id.')');
    }

    if ((bool) $this->option('dry-run')) {
        $this->warn('Dry-run: no se eliminó nada.');

        return 0;
    }

    $deleted = (clone $base)->delete();
    $this->info('Eliminados: '.$deleted);

    return 0;
})->purpose("Elimina usuarios cuyo nombre_cliente sea 'Importado' (registros fantasma de importación)");

Artisan::command('usuarios:backfill-baja-temporal {--dry-run : Solo muestra los cambios sin aplicarlos}', function () {
    $baja = \App\Models\EstatusServicio::whereRaw('LOWER(nombre) = ?', ['baja temporal'])->first();
    if (! $baja) {
        $baja = \App\Models\EstatusServicio::create(['nombre' => 'Baja temporal']);
    }

    $facturas = \App\Models\Factura::query()
        ->where('payload->otro', 'baja_temporal')
        ->orderBy('id')
        ->get(['id', 'numero_servicio', 'usuario_id']);

    $this->info('Facturas baja temporal encontradas: '.$facturas->count());

    $dry = (bool) $this->option('dry-run');
    $updated = 0;
    $skipped = 0;

    foreach ($facturas as $f) {
        $usuario = null;
        if (! empty($f->usuario_id)) {
            $usuario = \App\Models\Usuario::find($f->usuario_id);
        }
        if (! $usuario && ! empty($f->numero_servicio)) {
            $usuario = \App\Models\Usuario::where('numero_servicio', (string) $f->numero_servicio)->first();
        }
        if (! $usuario) {
            $skipped++;

            continue;
        }

        if ((int) $usuario->estatus_servicio_id === (int) $baja->id) {
            continue;
        }

        $this->line('Usuario '.$usuario->numero_servicio.' (id '.$usuario->id.') -> Baja temporal');
        $updated++;

        if ($dry) {
            continue;
        }

        $prev = ['estatus_servicio_id' => $usuario->estatus_servicio_id];
        $usuario->update(['estatus_servicio_id' => $baja->id]);

        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'actor_user_id' => null,
            'actor_role' => null,
            'actor_name' => null,
            'action' => 'backfill_usuario_baja_temporal',
            'table_name' => 'usuarios',
            'entity_type' => \App\Models\Usuario::class,
            'entity_id' => (string) $usuario->id,
            'prev_values' => json_encode($prev),
            'new_values' => json_encode(['estatus_servicio_id' => $baja->id]),
            'ip' => null,
            'user_agent' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->info('Actualizados: '.$updated);
    $this->info('Omitidos (sin usuario): '.$skipped);

    if ($dry) {
        $this->warn('Dry-run: no se aplicaron cambios.');
    }

    return 0;
})->purpose("Marca como 'Baja temporal' a usuarios con facturas cuyo payload->otro = baja_temporal");
