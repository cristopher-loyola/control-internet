<?php

use App\Http\Controllers\Pagos\PagosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:pagos'])
    ->prefix('pagos')
    ->name('pagos.')
    ->group(function () {
        Route::get('/', [PagosController::class, 'index'])->name('index');
        Route::get('/create', [PagosController::class, 'create'])->name('create');
        Route::post('/', [PagosController::class, 'store'])->name('store');
        Route::get('/{id}', [PagosController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PagosController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PagosController::class, 'update'])->name('update');
        Route::delete('/{id}', [PagosController::class, 'destroy'])->name('destroy');
    });

