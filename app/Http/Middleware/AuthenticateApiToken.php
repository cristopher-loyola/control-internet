<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['ok' => false, 'message' => 'Token requerido.'], 401);
        }

        $apiToken = \App\Models\ApiToken::findValid($bearer);

        if (! $apiToken) {
            return response()->json(['ok' => false, 'message' => 'Token inválido o expirado.'], 401);
        }

        $request->attributes->set('api_usuario', $apiToken->usuario);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
