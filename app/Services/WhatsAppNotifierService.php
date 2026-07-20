<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotifierService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.whatsapp.url'), '/');
    }

    private function token(): string
    {
        return (string) config('services.whatsapp.token');
    }

    public function estado(): array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/status');

            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'connected' => false, 'message' => 'No se pudo contactar el servicio de WhatsApp.'];
        }
    }

    public function qrImage(): ?string
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/qr');

            return $response->successful() ? $response->body() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function relink(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-api-token' => $this->token()])
                ->post($this->baseUrl().'/relink');

            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo contactar el servicio de WhatsApp.'];
        }
    }

    public function destinatario(): ?string
    {
        $setting = AppSetting::find('whatsapp_destinatario');

        return $setting->value['numero'] ?? null;
    }

    public function guardarDestinatario(string $numero): void
    {
        AppSetting::updateOrCreate(
            ['key' => 'whatsapp_destinatario'],
            ['value' => ['numero' => $numero]]
        );
    }

    /**
     * Envía un mensaje al número destinatario configurado. No lanza
     * excepciones: si el servicio de WhatsApp está caído, el flujo de pago
     * no debe romperse por esto, solo se registra en el log.
     */
    public function enviarNotificacionReactivacion(string $numeroServicio, ?string $nombreCliente): bool
    {
        $destinatario = $this->destinatario();
        if (! $destinatario) {
            Log::warning('WhatsApp: no hay número destinatario configurado, no se envió notificación de reactivación.', [
                'numero_servicio' => $numeroServicio,
            ]);

            return false;
        }

        $mensaje = "Activar {$numeroServicio}".($nombreCliente ? " - {$nombreCliente}" : '')
            ."\nEl cliente pagó y estaba pendiente de corte por adeudo. Favor de reactivar su servicio.";

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-api-token' => $this->token()])
                ->post($this->baseUrl().'/send', [
                    'to' => $destinatario,
                    'message' => $mensaje,
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp: fallo al enviar notificación de reactivación.', [
                    'numero_servicio' => $numeroServicio,
                    'response' => $response->json(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: excepción al enviar notificación de reactivación.', [
                'numero_servicio' => $numeroServicio,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
