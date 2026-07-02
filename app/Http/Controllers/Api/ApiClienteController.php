<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoStripe;
use App\Services\MorosidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiClienteController extends Controller
{
    public function __construct(private MorosidadService $morosidad) {}

    public function perfil(Request $request): JsonResponse
    {
        $usuario = $request->attributes->get('api_usuario');

        return response()->json([
            'ok'     => true,
            'cliente' => [
                'numero'       => $usuario->numero_servicio,
                'nombre'       => $usuario->nombre_cliente,
                'domicilio'    => $usuario->domicilio,
                'telefono'     => $usuario->telefono,
                'plan'         => $usuario->tarifa,
                'megas'        => $usuario->megas,
                'tecnologia'   => $usuario->tecnologia,
                'estatus'      => optional($usuario->estatusServicio)->nombre,
                'estado'       => optional($usuario->estado)->nombre,
            ],
        ]);
    }

    public function deuda(Request $request): JsonResponse
    {
        $usuario = $request->attributes->get('api_usuario');
        $periodo = now()->format('Y-m');

        $a = $this->morosidad->calcularAdeudoUsuario(
            (string) $usuario->numero_servicio,
            $periodo
        );

        if (! ($a['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $a['message'] ?? 'Error'], 500);
        }

        return response()->json([
            'ok'   => true,
            'deuda' => [
                'pendiente'            => $a['pendiente'],
                'mensualidad'          => $a['mensualidad'],
                'recargo'              => $a['recargo'],
                'pagado_parcial'       => $a['pagado_parcial'],
                'adeudo_manual'        => $a['adeudo_manual'],
                'descripcion_manual'   => $a['descripcion_manual'],
                'meses_adeudo'         => $a['meses_adeudo'],
                'lista_meses'          => $a['lista_meses'],
                'desde_periodo'        => $a['desde_periodo'],
                'desde_mes'            => $a['desde_mes_label'],
                'hasta_mes'            => $a['hasta_mes_label'],
                'vencimiento'          => $a['vencimiento'],
                'cubierto_este_mes'    => $a['cubierto_este_mes'],
                'al_corriente'         => $a['pendiente'] <= 0,
            ],
        ]);
    }

    public function transacciones(Request $request): JsonResponse
    {
        $usuario = $request->attributes->get('api_usuario');

        $rows = PagoStripe::where('usuario_id', $usuario->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['payment_intent_id', 'monto', 'estado', 'periodo', 'pagado_at', 'created_at', 'motivo_fallo']);

        return response()->json([
            'ok'            => true,
            'transacciones' => $rows->map(fn($r) => [
                'id'           => $r->payment_intent_id,
                'monto'        => (float) $r->monto,
                'estado'       => $r->estado,
                'periodo'      => $r->periodo,
                'motivo_fallo' => $r->motivo_fallo,
                'fecha'        => ($r->pagado_at ?? $r->created_at)?->toIso8601String(),
            ]),
        ]);
    }
}
