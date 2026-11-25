<?php

use App\Http\Controllers\Mesas\MesasController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:mesas'])
    ->prefix('mesas')
    ->name('mesas.')
    ->group(function () {
        Route::get('/', [MesasController::class, 'index'])->name('index');
        Route::get('/pagos', [MesasController::class, 'pagos'])->name('pagos');
        Route::get('/corte', [MesasController::class, 'corte'])->name('corte');
        Route::get('/historial', [MesasController::class, 'historial'])->name('historial');
        Route::get('/historial-cortes', [MesasController::class, 'historialCortes'])->name('historial-cortes');
        Route::delete('/pagos/eliminar/{id}', [MesasController::class, 'eliminarPago'])->name('pagos.eliminar');

        // API Routes for Mesas Payments (Independent)
        Route::get('/prepay-settings', [MesasController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/recibos/lookup', [MesasController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/pago-anterior', [MesasController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/deuda', [MesasController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::get('/recibos/prepay-status', [MesasController::class, 'recibosPrepayStatus'])->name('recibos.prepay.status');
        Route::get('/recibos/layout', [MesasController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [MesasController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
        Route::get('/recibos/facturas/{id}/verificar', [MesasController::class, 'verificarFactura'])->name('recibos.facturas.verificar');

        // Routes for Corte de Caja
        Route::post('/corte/iniciar', [MesasController::class, 'iniciarCorte'])->name('corte.iniciar');
        Route::post('/corte/finalizar', [MesasController::class, 'finalizarCorte'])->name('corte.finalizar');
        Route::post('/corte/reanudar', [MesasController::class, 'reanudarCorte'])->name('corte.reanudar');
        Route::get('/corte/activo', [MesasController::class, 'corteActivo'])->name('corte.activo');
        Route::get('/corte/exportar', [MesasController::class, 'exportarCorteExcel'])->name('corte.exportar');
        Route::get('/corte/{id}/detalle', [MesasController::class, 'corteDetalleJson'])->name('corte.detalle');
    });
