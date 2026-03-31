<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ZonaDashboardService
{
    public static function normalizeZona(string $zona): string
    {
        $z = trim($zona);
        $z = str_replace(['_', '-'], ' ', $z);
        $z = preg_replace('/\s+/u', ' ', $z) ?? $z;

        return mb_strtolower($z);
    }

    public static function stats(string $zona): array
    {
        $z = self::normalizeZona($zona);
        $estadoActivos = ['Activado', 'Activo'];
        $estadoDesactivos = ['Desactivado', 'Inactivo', 'Suspendido'];

        $base = Usuario::query()->whereRaw('LOWER(zona) = ?', [$z]);

        $total = (int) (clone $base)->count();
        $activos = (int) (clone $base)->whereHas('estado', fn ($q) => $q->whereIn('nombre', $estadoActivos))->count();
        $desactivados = (int) (clone $base)->whereHas('estado', fn ($q) => $q->whereIn('nombre', $estadoDesactivos))->count();
        $pendientes = (int) (clone $base)->whereHas('estatusServicio', fn ($q) => $q->whereIn('nombre', ['Pendiente de pago']))->count();

        return [
            'total' => $total,
            'activos' => $activos,
            'desactivados' => $desactivados,
            'pendientes' => $pendientes,
        ];
    }

    public static function recentPayments(string $zona, int $limit = 10): array
    {
        $z = self::normalizeZona($zona);
        $limit = max(1, min(50, $limit));

        $rows = Factura::query()
            ->whereNull('facturas.deleted_at')
            ->join('usuarios', 'usuarios.numero_servicio', '=', 'facturas.numero_servicio')
            ->whereRaw('LOWER(usuarios.zona) = ?', [$z])
            ->orderByDesc('facturas.id')
            ->limit($limit)
            ->get([
                'facturas.reference_number',
                'facturas.numero_servicio',
                'facturas.total',
                'facturas.created_at',
                DB::raw('usuarios.nombre_cliente as nombre_cliente'),
            ]);

        return $rows->map(function ($r) {
            return [
                'folio' => $r->reference_number,
                'numero' => (string) $r->numero_servicio,
                'nombre' => (string) ($r->nombre_cliente ?? ''),
                'total' => (float) $r->total,
                'fecha' => optional($r->created_at)->format('Y-m-d H:i'),
            ];
        })->all();
    }

    public static function chartNewClientsLast7Days(string $zona): array
    {
        $z = self::normalizeZona($zona);
        $dateExpr = 'COALESCE(fecha_contratacion, DATE(created_at))';
        $from = now()->copy()->subDays(6)->toDateString();
        $to = now()->toDateString();

        $rows = Usuario::query()
            ->selectRaw("{$dateExpr} as d, COUNT(*) as c")
            ->whereRaw('LOWER(zona) = ?', [$z])
            ->whereRaw("{$dateExpr} between ? and ?", [$from, $to])
            ->groupBy('d')
            ->orderBy('d', 'asc')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->d] = (int) $r->c;
        }

        $labels = [];
        $values = [];
        $cursor = now()->copy()->subDays(6)->startOfDay();
        for ($i = 0; $i < 7; $i++) {
            $k = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $values[] = $map[$k] ?? 0;
            $cursor->addDay();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
