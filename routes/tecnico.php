<?php

use App\Http\Controllers\Tecnico\TecnicoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:tecnico'])
    ->prefix('tecnico')
    ->name('tecnico.')
    ->group(function () {
        Route::get('/', [TecnicoController::class, 'index'])->name('index');
        Route::get('/create', [TecnicoController::class, 'create'])->name('create');
        Route::post('/', [TecnicoController::class, 'store'])->name('store');
        Route::get('/{id}', [TecnicoController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [TecnicoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TecnicoController::class, 'update'])->name('update');
        Route::delete('/{id}', [TecnicoController::class, 'destroy'])->name('destroy');
    });

