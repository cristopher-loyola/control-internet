<?php

use App\Http\Controllers\Admin\CortesController;
use App\Http\Controllers\Tecnico\TecnicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:tecnico'])
    ->prefix('tecnico')
    ->name('tecnico.')
    ->group(function () {
        Route::get('/', [TecnicoController::class, 'index'])->name('index');

        // Módulo de Cortes (para perfil tecnico)
        Route::get('/cortes', [CortesController::class, 'index'])->name('cortes.index');
        Route::get('/reactivaciones', [CortesController::class, 'reactivacionesIndex'])->name('reactivaciones.index');
        Route::post('/cortes/{id}/update', [CortesController::class, 'updateCorte'])->name('cortes.update');
        Route::post('/cortes/cortadores', [CortesController::class, 'storeCortador'])->name('cortes.cortadores.store');
        Route::delete('/cortes/cortadores/{id}', [CortesController::class, 'destroyCortador'])->name('cortes.cortadores.destroy');
        Route::get('/create', [TecnicoController::class, 'create'])->name('create');
        Route::post('/', [TecnicoController::class, 'store'])->name('store');
        Route::get('/{id}', [TecnicoController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TecnicoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TecnicoController::class, 'update'])->name('update');
        Route::delete('/{id}', [TecnicoController::class, 'destroy'])->name('destroy');
    });

