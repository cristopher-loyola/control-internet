<?php

use App\Http\Controllers\Contrataciones\ContratacionesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:contrataciones'])
    ->prefix('contrataciones')
    ->name('contrataciones.')
    ->group(function () {
        Route::get('/', [ContratacionesController::class, 'index'])->name('index');
        Route::get('/clientes', [ContratacionesController::class, 'clientes'])->name('clientes.index');
        Route::post('/clientes', [ContratacionesController::class, 'clientesStore'])->name('clientes.store');
        Route::post('/clientes/editar', [ContratacionesController::class, 'clientesEditStore'])->name('clientes.edit');
        Route::get('/clientes/historial/buscar', [ContratacionesController::class, 'clientesHistorialBuscar'])->name('clientes.historial.buscar');
        Route::get('/clientes/{id}', [ContratacionesController::class, 'clientesShow'])->name('clientes.show');
        Route::get('/clientes/{numero}/historial', [ContratacionesController::class, 'clientesHistorial'])->name('clientes.historial');
        Route::get('/create', [ContratacionesController::class, 'create'])->name('create');
        Route::post('/', [ContratacionesController::class, 'store'])->name('store');
        Route::get('/{id}', [ContratacionesController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ContratacionesController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ContratacionesController::class, 'update'])->name('update');
        Route::delete('/{id}', [ContratacionesController::class, 'destroy'])->name('destroy');
    });
