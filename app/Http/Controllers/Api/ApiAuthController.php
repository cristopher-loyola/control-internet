<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'numero_servicio' => ['required', 'string'],
            'telefono'        => ['required', 'string'],
        ]);

        $numero   = trim($request->input('numero_servicio'));
        $telefono = preg_replace('/\D/', '', trim($request->input('telefono')));

        $usuario = Usuario::where('numero_servicio', $numero)->first();

        $errorMsg = 'Número de servicio o teléfono incorrecto.';

        if (! $usuario) {
            return response()->json(['ok' => false, 'message' => $errorMsg], 401);
        }

        if (empty($usuario->telefono)) {
            return response()->json([
                'ok'      => false,
                'message' => 'Para acceder comunícate con nosotros para registrar tu teléfono.',
            ], 403);
        }

        $telefonoDB = preg_replace('/\D/', '', (string) $usuario->telefono);

        if (! hash_equals($telefonoDB, $telefono)) {
            return response()->json(['ok' => false, 'message' => $errorMsg], 401);
        }

        ['plain' => $plain, 'model' => $apiToken] = ApiToken::generate($usuario);

        return response()->json([
            'ok'      => true,
            'token'   => $plain,
            'expires' => $apiToken->expires_at->toIso8601String(),
            'cliente' => [
                'numero' => $usuario->numero_servicio,
                'nombre' => $usuario->nombre_cliente,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return response()->json(['ok' => true, 'message' => 'Sesión cerrada.']);
    }
}
