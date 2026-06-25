<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PagoStripe;
use App\Services\MorosidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class ApiPagosController extends Controller
{
    public function __construct(private MorosidadService $morosidad) {}

    public function crearIntent(Request $request): JsonResponse
    {
        $usuario = $request->attributes->get('api_usuario');
        $periodo = now()->format('Y-m');

        $a = $this->morosidad->calcularAdeudoUsuario(
            (string) $usuario->numero_servicio,
            $periodo
        );

        if (! ($a['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => 'No se pudo calcular el adeudo.'], 500);
        }

        $pendiente = (float) ($a['pendiente'] ?? 0);

        if ($pendiente <= 0) {
            return response()->json(['ok' => false, 'message' => 'No tienes saldo pendiente.'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $intent = PaymentIntent::create([
            'amount'   => (int) round($pendiente * 100), // centavos
            'currency' => 'mxn',
            'metadata' => [
                'usuario_id'      => $usuario->id,
                'numero_servicio' => $usuario->numero_servicio,
                'periodo'         => $periodo,
            ],
        ]);

        PagoStripe::create([
            'usuario_id'        => $usuario->id,
            'payment_intent_id' => $intent->id,
            'monto'             => $pendiente,
            'estado'            => 'pendiente',
            'periodo'           => $periodo,
        ]);

        return response()->json([
            'ok'            => true,
            'client_secret' => $intent->client_secret,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException) {
            return response()->json(['ok' => false], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $pi = $event->data->object;

            PagoStripe::where('payment_intent_id', $pi->id)
                ->update(['estado' => 'completado', 'pagado_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
