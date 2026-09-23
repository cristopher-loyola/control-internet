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
        $recibos = Factura::withTrashed()->selectRaw("id, created_at, 'recibo' as origen")
            ->where('payload->manual_total_enabled', true)
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<', $inicio->copy()->addMonth())->toBase();
        $ajustes = DB::table('audit_logs')->selectRaw("id, created_at, 'cliente' as origen")
            ->where('action', 'cliente_adeudo_override')->where('table_name', 'usuarios')
            ->where('created_at', '>=', $inicio)
            ->where('created_at', '<', $inicio->copy()->addMonth());
        $query = DB::query()->fromSub($recibos->unionAll($ajustes), 'modificaciones')
            ->orderByDesc('created_at')->orderByDesc('id')->orderBy('origen');
        $paginator = $excel ? null : $query->paginate(50)->withQueryString();
        $eventos = $excel ? $query->get() : $paginator->getCollection();
        $facturas = Factura::withTrashed()->with('cajero')
            ->whereIn('id', $eventos->where('origen', 'recibo')->pluck('id'))->get()->keyBy('id');
        $ajustesGuardados = DB::table('audit_logs')
            ->whereIn('id', $eventos->where('origen', 'cliente')->pluck('id'))->get()->keyBy('id');
        $auditorias = DB::table('audit_logs')
            ->where('action', 'factura_total_override')
            ->where('table_name', 'facturas')
            ->whereIn('entity_id', $facturas->modelKeys())
            ->orderBy('id')->get()->keyBy('entity_id');

        $rows = $eventos->map(function ($evento) use ($facturas, $auditorias, $ajustesGuardados) {
            if ($evento->origen === 'cliente') {
                $ajuste = $ajustesGuardados->get($evento->id);
                $previos = json_decode($ajuste->prev_values, true) ?? [];
                $nuevos = json_decode($ajuste->new_values, true) ?? [];

                return (object) [
                    'folio' => '—',
                    'origen' => 'Clientes (botón azul)',
                    'es_cliente' => true,
                    'fecha' => Carbon::parse($ajuste->created_at)->format('d/m/Y H:i'),
                    'numero' => $nuevos['numero_servicio'] ?? 'No registrado',
                    'cliente' => $nuevos['nombre_cliente'] ?? 'No registrado',
                    'total_anterior' => $previos['total'] ?? null,
                    'total' => $nuevos['total'] ?? null,
                    'motivo' => ($nuevos['reason'] ?? '') !== '' ? $nuevos['reason'] : 'Sin descripción',
                    'usuario' => $nuevos['modificado_por'] ?? $ajuste->actor_name ?? 'No registrado',
                    'cobro' => 'No aplica',
                    'estado' => null,
                ];
            }

            $factura = $facturas->get($evento->id);
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
                'origen' => 'Pagos (Modificar total)',
                'es_cliente' => false,
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
