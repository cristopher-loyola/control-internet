<?php

namespace App\Http\Middleware;

use App\Services\BajaTemporalService;
use Closure;
use Illuminate\Http\Request;

class ActivarBajasTemporales
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            app(BajaTemporalService::class)->activarProgramadas();
        }

        return $next($request);
    }
}
