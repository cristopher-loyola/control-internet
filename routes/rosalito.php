<?php

use App\Http\Controllers\Rosalito\RosalitoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:rosalito'])
    ->prefix('rosalito')
    ->name('rosalito.')
    ->group(function () {
        Route::get('/', [RosalitoController::class, 'index'])->name('index');
        Route::get('/pagos', [RosalitoController::class, 'pagos'])->name('pagos');
        Route::get('/corte', [RosalitoController::class, 'corte'])->name('corte');
    });
