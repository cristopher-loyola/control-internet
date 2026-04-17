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
        Route::get('/historial', [PozoHondoController::class, 'historial'])->name('historial');
        Route::get('/historial-cortes', [PozoHondoController::class, 'historialCortes'])->name('historial-cortes');
        Route::delete('/pagos/eliminar/{id}', [PozoHondoController::class, 'eliminarPago'])->name('pagos.eliminar');

        // API Routes for Pozo Hondo Payments (Independent)
        Route::get('/prepay-settings', [PozoHondoController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/recibos/lookup', [PozoHondoController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/pago-anterior', [PozoHondoController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/deuda', [PozoHondoController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::get('/recibos/prepay-status', [PozoHondoController::class, 'recibosPrepayStatus'])->name('recibos.prepay.status');
        Route::get('/recibos/layout', [PozoHondoController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [PozoHondoController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');

        // Routes for Corte de Caja
        Route::post('/corte/iniciar', [PozoHondoController::class, 'iniciarCorte'])->name('corte.iniciar');
        Route::post('/corte/finalizar', [PozoHondoController::class, 'finalizarCorte'])->name('corte.finalizar');
        Route::post('/corte/reanudar', [PozoHondoController::class, 'reanudarCorte'])->name('corte.reanudar');
        Route::get('/corte/activo', [PozoHondoController::class, 'corteActivo'])->name('corte.activo');
        Route::get('/corte/exportar', [PozoHondoController::class, 'exportarCorteExcel'])->name('corte.exportar');
    });
