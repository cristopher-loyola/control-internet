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
        Route::get('/historial', [RosalitoController::class, 'historial'])->name('historial');
        Route::delete('/pagos/eliminar/{id}', [RosalitoController::class, 'eliminarPago'])->name('pagos.eliminar');

        // API Routes for Rosalito Payments (Independent)
        Route::get('/prepay-settings', [RosalitoController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/recibos/lookup', [RosalitoController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/pago-anterior', [RosalitoController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/deuda', [RosalitoController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::get('/recibos/prepay-status', [RosalitoController::class, 'recibosPrepayStatus'])->name('recibos.prepay.status');
        Route::get('/recibos/layout', [RosalitoController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [RosalitoController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
    });
