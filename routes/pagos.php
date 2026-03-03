<?php

use App\Http\Controllers\Pagos\PagosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:pagos'])
    ->prefix('pagos')
    ->name('pagos.')
    ->group(function () {
        Route::get('/', [PagosController::class, 'index'])->name('index');
        
        // Rutas módulo recibos
        Route::get('/recibos', [PagosController::class, 'recibos'])->name('recibos');
        Route::get('/recibos/lookup', [PagosController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/layout', [PagosController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [PagosController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
        Route::get('/recibos/facturas', [PagosController::class, 'recibosFacturasIndex'])->name('recibos.facturas.index');
        Route::get('/recibos/facturas/{id}', [PagosController::class, 'recibosFacturaShow'])->name('recibos.facturas.show');
        Route::get('/recibos/folio/{ref}', [PagosController::class, 'recibosFacturaByFolio'])->name('recibos.facturas.by_folio');
        Route::get('/recibos/pago-anterior', [PagosController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/historial', [PagosController::class, 'recibosHistorial'])->name('recibos.historial');
        Route::get('/recibos/historial/export', [PagosController::class, 'recibosHistorialExport'])->name('recibos.historial.export');
        Route::post('/recibos/facturas/{id}/cancel', [PagosController::class, 'recibosFacturaCancel'])->name('recibos.facturas.cancel');

        Route::get('/create', [PagosController::class, 'create'])->name('create');
        Route::post('/', [PagosController::class, 'store'])->name('store');
        Route::get('/{id}', [PagosController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PagosController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PagosController::class, 'update'])->name('update');
        Route::delete('/{id}', [PagosController::class, 'destroy'])->name('destroy');
    });
