<?php

use App\Http\Controllers\Chivato\ChivatoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:chivato'])
    ->prefix('chivato')
    ->name('chivato.')
    ->group(function () {
        Route::get('/', [ChivatoController::class, 'index'])->name('index');
        Route::get('/pagos', [ChivatoController::class, 'pagos'])->name('pagos');
        Route::get('/corte', [ChivatoController::class, 'corte'])->name('corte');
    });
