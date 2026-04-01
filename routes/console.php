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
