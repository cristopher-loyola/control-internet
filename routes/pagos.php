<?php

use App\Http\Controllers\Admin\CortesController;
use App\Http\Controllers\Pagos\PagosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:pagos'])
    ->prefix('pagos')
    ->name('pagos.')
    ->group(function () {
        Route::get('/', [PagosController::class, 'index'])->name('index');
        
        // Rutas módulo recibos
        Route::get('/recibos', [PagosController::class, 'recibos'])->name('recibos');
        Route::get('/corte', [PagosController::class, 'corte'])->name('corte');
        Route::get('/corte-data', [\App\Http\Controllers\Admin\DashboardController::class, 'corteCaja'])->name('corte.data');

        // Módulo de Cortes (para perfil pagos)
        Route::get('/cortes', [CortesController::class, 'index'])->name('cortes.index');
        Route::post('/cortes/{id}/update', [CortesController::class, 'updateCorte'])->name('cortes.update');
        Route::post('/cortes/cortadores', [CortesController::class, 'storeCortador'])->name('cortes.cortadores.store');
        Route::delete('/cortes/cortadores/{id}', [CortesController::class, 'destroyCortador'])->name('cortes.cortadores.destroy');

        // Ruta de clientes (solo lectura y edición para pagos, sin crear ni eliminar)
        Route::get('/clientes', [PagosController::class, 'clientes'])->name('clientes.index');
        Route::post('/clientes/editar', [PagosController::class, 'clientesEditStore'])->name('clientes.edit');
        Route::get('/clientes/historial/buscar', [PagosController::class, 'clientesHistorialBuscar'])->name('clientes.historial.buscar');
        Route::get('/clientes/{id}', [PagosController::class, 'clientesShow'])->name('clientes.show');
        Route::get('/clientes/{numero}/historial', [PagosController::class, 'clientesHistorial'])->name('clientes.historial');
        Route::get('/recibos/lookup', [PagosController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/layout', [PagosController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::get('/recibos/deuda', [PagosController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::post('/recibos/facturas', [PagosController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
        Route::get('/recibos/facturas', [PagosController::class, 'recibosFacturasIndex'])->name('recibos.facturas.index');
        Route::get('/recibos/facturas/{id}', [PagosController::class, 'recibosFacturaShow'])->name('recibos.facturas.show');
        Route::get('/recibos/folio/{ref}', [PagosController::class, 'recibosFacturaByFolio'])->name('recibos.facturas.by_folio');
        Route::get('/recibos/pago-anterior', [PagosController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/historial', [PagosController::class, 'recibosHistorial'])->name('recibos.historial');
        Route::get('/recibos/historial/export', [PagosController::class, 'recibosHistorialExport'])->name('recibos.historial.export');
        Route::post('/recibos/facturas/{id}/cancel', [PagosController::class, 'recibosFacturaCancel'])->name('recibos.facturas.cancel');

        // Configuración de pago anticipado (consumido por la UI de pagos)
        Route::get('/prepay-settings', [PagosController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/create', [PagosController::class, 'create'])->name('create');
        Route::post('/', [PagosController::class, 'store'])->name('store');
        Route::get('/{id}', [PagosController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PagosController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PagosController::class, 'update'])->name('update');
        Route::delete('/{id}', [PagosController::class, 'destroy'])->name('destroy');
    });
