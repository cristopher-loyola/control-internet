<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/clientes', [AdminController::class, 'clientes'])->name('clientes.index');
        Route::post('/clientes', [AdminController::class, 'clientesStore'])->name('clientes.store');
        Route::post('/clientes/editar', [AdminController::class, 'clientesEditStore'])->name('clientes.edit');
        Route::get('/clientes/{id}', [AdminController::class, 'clientesShow'])->name('clientes.show');
        Route::delete('/clientes/{id}', [AdminController::class, 'clientesDestroy'])->name('clientes.destroy');
        Route::get('/create', [AdminController::class, 'create'])->name('create');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
    });
