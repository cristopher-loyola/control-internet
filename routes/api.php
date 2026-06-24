<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiClienteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
*/

// Rutas públicas (sin token)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/login',  [ApiAuthController::class, 'login']);
});

// Rutas protegidas (requieren Bearer token)
Route::middleware(['api.auth', 'throttle:60,1'])->group(function () {
    Route::post('/auth/logout', [ApiAuthController::class, 'logout']);

    Route::get('/cliente/perfil', [ApiClienteController::class, 'perfil']);
    Route::get('/cliente/deuda',  [ApiClienteController::class, 'deuda']);
});
