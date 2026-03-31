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
        Route::get('/historial', [ChivatoController::class, 'historial'])->name('historial');
        Route::delete('/pagos/eliminar/{id}', [ChivatoController::class, 'eliminarPago'])->name('pagos.eliminar');

        // API Routes for Chivato Payments (Independent)
        Route::get('/prepay-settings', [ChivatoController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/recibos/lookup', [ChivatoController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/pago-anterior', [ChivatoController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/deuda', [ChivatoController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::get('/recibos/prepay-status', [ChivatoController::class, 'recibosPrepayStatus'])->name('recibos.prepay.status');
        Route::get('/recibos/layout', [ChivatoController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [ChivatoController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
    });
