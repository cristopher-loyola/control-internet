<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModificacionesController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'mes' => ['sometimes', 'required', 'date_format:Y-m'],
            'format' => ['sometimes', 'required', 'in:excel'],
        ]);
        $mes = $validated['mes'] ?? now()->format('Y-m');
        $inicio = Carbon::createFromFormat('!Y-m', $mes);
        $excel = ($validated['format'] ?? null) === 'excel';

        // La fecha de captura identifica la modificación, aunque cambie el mes contable.
        $query = Factura::withTrashed()->with('cajero')
            ->where('payload->manual_total_enabled', true)
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<', $inicio->copy()->addMonth())
            ->orderByDesc('created_at')->orderByDesc('id');
        $paginator = $excel ? null : $query->paginate(50)->withQueryString();
        $facturas = $excel ? $query->get() : $paginator->getCollection();
        $auditorias = DB::table('audit_logs')
            ->where('action', 'factura_total_override')
            ->where('table_name', 'facturas')
            ->whereIn('entity_id', $facturas->modelKeys())
            ->orderBy('id')->get()->keyBy('entity_id');

        $rows = $facturas->map(function ($factura) use ($auditorias) {
            $payload = $factura->payload ?? [];
            $auditoria = $auditorias->get($factura->id);
            $previos = json_decode($auditoria->prev_values ?? '{}', true) ?? [];
            // Los registros antiguos podían copiar el total editado como anterior.
            // Conservar valores distintos ya auditados; no presentar los ambiguos como reales.
            $totalAnterior = array_key_exists('manual_total_previous', $payload)
                ? $payload['manual_total_previous']
                : (isset($previos['total']) && (float) $previos['total'] !== (float) $factura->total
                    ? $previos['total'] : null);

            return (object) [
                'folio' => str_pad((string) $factura->reference_number, 8, '0', STR_PAD_LEFT),
                'fecha' => $factura->created_at?->format('d/m/Y H:i'),
                'numero' => $factura->numero_servicio,
                'cliente' => $payload['nombre'] ?? 'No registrado',
                'total_anterior' => $totalAnterior,
                'total' => $factura->total,
                'motivo' => $payload['manual_total_reason'] ?? 'No registrado',
                'usuario' => $auditoria->actor_name ?? $factura->cajero?->name ?? 'No registrado',
                'cobro' => $payload['cobro'] ?? 'No registrado',
                'estado' => $factura->trashed() ? 'Cancelado' : 'Vigente',
            ];
        });
        $data = [
            'mes' => $mes,
            'mesLabel' => $inicio->locale('es')->translatedFormat('F Y'),
            'rows' => $rows,
            'paginator' => $paginator,
            'cantidad' => $paginator ? $paginator->total() : $rows->count(),
        ];

        if ($excel) {
            return response()->streamDownload(function () use ($data) {
                echo "\xEF\xBB\xBF";
                echo view('admin.modificaciones.excel', $data)->render();
            }, 'historial_modificaciones_'.$mes.'.xls', [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            ]);
        }

        return view('admin.modificaciones.index', $data);
    }
}
