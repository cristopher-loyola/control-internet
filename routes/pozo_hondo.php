<?php

use App\Http\Controllers\PozoHondo\PozoHondoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:pozo_hondo'])
    ->prefix('pozo-hondo')
    ->name('pozo_hondo.')
    ->group(function () {
        Route::get('/', [PozoHondoController::class, 'index'])->name('index');
        Route::get('/pagos', [PozoHondoController::class, 'pagos'])->name('pagos');
        Route::get('/corte', [PozoHondoController::class, 'corte'])->name('corte');
    });
