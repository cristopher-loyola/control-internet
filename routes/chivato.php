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
        Route::get('/historial-cortes', [ChivatoController::class, 'historialCortes'])->name('historial-cortes');
        Route::delete('/pagos/eliminar/{id}', [ChivatoController::class, 'eliminarPago'])->name('pagos.eliminar');

        // API Routes for Chivato Payments (Independent)
        Route::get('/prepay-settings', [ChivatoController::class, 'prepaySettings'])->name('prepay.settings');
        Route::get('/recibos/lookup', [ChivatoController::class, 'recibosLookup'])->name('recibos.lookup');
        Route::get('/recibos/pago-anterior', [ChivatoController::class, 'recibosPagoAnterior'])->name('recibos.prev');
        Route::get('/recibos/deuda', [ChivatoController::class, 'recibosDeuda'])->name('recibos.deuda');
        Route::get('/recibos/prepay-status', [ChivatoController::class, 'recibosPrepayStatus'])->name('recibos.prepay.status');
        Route::get('/recibos/layout', [ChivatoController::class, 'recibosLayoutGet'])->name('recibos.layout.get');
        Route::post('/recibos/facturas', [ChivatoController::class, 'recibosFacturaStore'])->name('recibos.facturas.store');
        Route::post('/recibos/ticket-pdf', [ChivatoController::class, 'ticketPdf'])->name('recibos.ticket-pdf');
    });

// Servir el PDF del ticket sin sesión (URL firmada temporal): lo descarga la
// app Epson TM Print Assistant en la tablet, que no comparte cookies del navegador.
Route::get('/chivato/recibos/ticket-pdf/{file}', [ChivatoController::class, 'ticketPdfShow'])
    ->middleware('signed')
    ->name('chivato.recibos.ticket-pdf.show');

Route::middleware(['auth', 'role:chivato'])
    ->prefix('chivato')
    ->name('chivato.')
    ->group(function () {

        // Routes for Corte de Caja
        Route::post('/corte/iniciar', [ChivatoController::class, 'iniciarCorte'])->name('corte.iniciar');
        Route::post('/corte/finalizar', [ChivatoController::class, 'finalizarCorte'])->name('corte.finalizar');
        Route::post('/corte/reanudar', [ChivatoController::class, 'reanudarCorte'])->name('corte.reanudar');
        Route::get('/corte/activo', [ChivatoController::class, 'corteActivo'])->name('corte.activo');
        Route::get('/corte/exportar', [ChivatoController::class, 'exportarCorteExcel'])->name('corte.exportar');
    });
