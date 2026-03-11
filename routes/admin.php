<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/corte', [DashboardController::class, 'corteView'])->name('corte.view');
        Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
        Route::get('/dashboard/corte', [DashboardController::class, 'corteCaja'])->name('dashboard.corte');
        Route::get('/dashboard/export', [DashboardController::class, 'exportResumen'])->name('dashboard.export');
        Route::get('/dashboard/cancelados', [DashboardController::class, 'canceladosIndex'])->name('dashboard.cancelados');
        Route::get('/dashboard/desactivados', [DashboardController::class, 'desactivadosIndex'])->name('dashboard.desactivados');
        Route::get('/dashboard/morosos', [DashboardController::class, 'morososIndex'])->name('dashboard.morosos');
        Route::get('/dashboard/morosos/export', [DashboardController::class, 'morososExport'])->name('dashboard.morosos.export');
        Route::get('/dashboard/prepay-settings', [DashboardController::class, 'prepaySettings'])->name('dashboard.prepay.settings');
        Route::get('/dashboard/pagos-adelantados', [DashboardController::class, 'prepayClientsIndex'])->name('dashboard.prepay.index');
        Route::get('/pagos', [AdminController::class, 'pagos'])->name('pagos.index');
        Route::get('/pagos/lookup', [AdminController::class, 'pagosLookup'])->name('pagos.lookup');
        Route::post('/pagos/layout', [AdminController::class, 'pagosLayoutStore'])->name('pagos.layout.store');
        Route::get('/pagos/layout', [AdminController::class, 'pagosLayoutGet'])->name('pagos.layout.get');
        Route::get('/pagos/deuda', [AdminController::class, 'pagosDeuda'])->name('pagos.deuda');
        Route::post('/pagos/facturas', [AdminController::class, 'pagosFacturaStore'])->name('pagos.facturas.store');
        Route::get('/pagos/facturas', [AdminController::class, 'pagosFacturasIndex'])->name('pagos.facturas.index');
        Route::get('/pagos/facturas/{id}', [AdminController::class, 'pagosFacturaShow'])->name('pagos.facturas.show');
        Route::get('/pagos/folio/{ref}', [AdminController::class, 'pagosFacturaByFolio'])->name('pagos.facturas.by_folio');
        Route::get('/pagos/previo', [AdminController::class, 'pagosPagoAnterior'])->name('pagos.prev');
        Route::get('/pagos/historial', [AdminController::class, 'pagosHistorial'])->name('pagos.historial');
        Route::get('/pagos/historial/export', [AdminController::class, 'pagosHistorialExport'])->name('pagos.historial.export');
        Route::post('/pagos/facturas/{id}/cancel', [AdminController::class, 'pagosFacturaCancel'])->name('pagos.facturas.cancel');
        Route::get('/clientes', [AdminController::class, 'clientes'])->name('clientes.index');
        Route::post('/clientes', [AdminController::class, 'clientesStore'])->name('clientes.store');
        Route::post('/clientes/editar', [AdminController::class, 'clientesEditStore'])->name('clientes.edit');
        Route::get('/clientes/historial/buscar', [AdminController::class, 'clientesHistorialBuscar'])->name('clientes.historial.buscar');
        Route::get('/clientes/{id}', [AdminController::class, 'clientesShow'])->name('clientes.show');
        Route::get('/clientes/{numero}/historial', [AdminController::class, 'clientesHistorial'])->name('clientes.historial');
        Route::post('/clientes/import', [AdminController::class, 'clientesImport'])->name('clientes.import');
        Route::delete('/clientes/{id}', [AdminController::class, 'clientesDestroy'])->name('clientes.destroy');
        Route::get('/create', [AdminController::class, 'create'])->name('create');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
    });
