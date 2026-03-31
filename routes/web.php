<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    if (! $user) {
        abort(404);
    }

    return match ($user->role ?? null) {
        'admin' => redirect()->route('admin.index'),
        'tecnico' => redirect()->route('tecnico.index'),
        'pagos' => redirect()->route('pagos.index'),
        'contrataciones' => redirect()->route('contrataciones.index'),
        'rosalito' => redirect()->route('rosalito.index'),
        'pozo_hondo' => redirect()->route('pozo_hondo.index'),
        'chivato' => redirect()->route('chivato.index'),
        default => abort(404),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/tecnico.php';
require __DIR__.'/pagos.php';
require __DIR__.'/contrataciones.php';
require __DIR__.'/rosalito.php';
require __DIR__.'/pozo_hondo.php';
require __DIR__.'/chivato.php';
