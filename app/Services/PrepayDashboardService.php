<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class PrepayDashboardService
{
    public static function normalizeNombre(?string $nombre, int $minLen = 3, int $maxLen = 80): ?string
    {
        $s = trim((string) ($nombre ?? ''));
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        if ($s === '') {
            return null;
        }
        $len = mb_strlen($s);
        if ($len < $minLen || $len > $maxLen) {
            return null;
        }
        if (! preg_match("/^[\\p{L}\\p{N}\\s\\.\\-']+$/u", $s)) {
            return null;
        }

        return $s;
    }

    public static function normalizeNumero(?string $numero, int $minLen = 3, int $maxLen = 10): ?string
    {
        $n = trim((string) ($numero ?? ''));
        if ($n === '' || ! ctype_digit($n)) {
            return null;
        }
        $len = strlen($n);
        if ($len < $minLen || $len > $maxLen) {
            return null;
        }

        return $n;
    }

    public static function parseQuery(?string $q): ?array
    {
        $raw = trim((string) ($q ?? ''));
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $n = self::normalizeNumero($raw);
            if (! $n) {
                return null;
            }

            return ['type' => 'numero', 'value' => $n];
        }

        $name = self::normalizeNombre($raw);
        if (! $name) {
            return null;
        }

        return ['type' => 'nombre', 'value' => $name];
    }

    public static function venceAt(?Carbon $desde, int $meses): ?Carbon
    {
        if (! $desde) {
            return null;
        }
        if ($meses <= 0) {
            return null;
        }

        return $desde->copy()->addMonths($meses)->endOfDay();
    }

    public static function estadoPorVencimiento(?Carbon $venceAt, ?Carbon $now = null, int $soonDays = 7): array
    {
        $now = $now ?: now();
        if (! $venceAt) {
            return [
                'estado' => 'Desconocido',
                'vencido' => false,
                'expira_pronto' => false,
                'dias_para_vencer' => null,
            ];
        }

        $venceDate = $venceAt->copy()->startOfDay();
        $today = $now->copy()->startOfDay();
        $diff = (int) $today->diffInDays($venceDate, false);
        $vencido = $diff < 0;
        $expiraPronto = ! $vencido && $diff <= $soonDays;

        return [
            'estado' => $vencido ? 'Vencido' : 'Activo',
            'vencido' => $vencido,
            'expira_pronto' => $expiraPronto,
            'dias_para_vencer' => $diff,
        ];
    }
}
