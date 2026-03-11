<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Factura;
use App\Models\Usuario;
use App\Models\Inventario;
use Dompdf\Dompdf;
use App\Models\CargoMora;
use App\Models\PrepaySetting;

class DashboardController extends Controller
{
    public function corteView()
    {
        return view('admin.corte');
    }

    public function canceladosIndex(Request $request)
    {
        $cancelNames = ['Cancelado','Baja','Eliminado','Inactivo'];
        $usuarios = Usuario::with(['estado','estatusServicio'])
            ->whereHas('estatusServicio', function ($q) use ($cancelNames) {
                $q->whereIn('nombre', $cancelNames);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(50);
        return view('admin.usuarios_cancelados', compact('usuarios'));
    }

    public function prepaySettings(Request $request)
    {
        $rows = PrepaySetting::all()->pluck('enabled', 'paquete')->toArray();
        $defaults = [300=>true, 400=>true, 500=>true, 600=>true];
        $enabled = array_merge($defaults, $rows);
        $matrix = [
            6  => ['percent' => 10, 'totals' => [300=>1620, 400=>2160, 500=>2700, 600=>3240]],
            7  => ['percent' => 11, 'totals' => [300=>1869, 400=>2492, 500=>3115, 600=>3738]],
            8  => ['percent' => 12, 'totals' => [300=>2112, 400=>2816, 500=>3520, 600=>4224]],
            9  => ['percent' => 13, 'totals' => [300=>2349, 400=>3132, 500=>3915, 600=>4698]],
            10 => ['percent' => 14, 'totals' => [300=>2580, 400=>3440, 500=>4300, 600=>5160]],
            11 => ['percent' => 15, 'totals' => [300=>2805, 400=>3740, 500=>4675, 600=>5610]],
            12 => ['percent' => 16, 'totals' => [300=>3024, 400=>4032, 500=>5040, 600=>6048]],
        ];
        return response()->json(['ok'=>true,'enabled'=>$enabled,'matrix'=>$matrix]);
    }

    public function desactivadosIndex(Request $request)
    {
        $downNames = ['Desactivado','Inactivo','Suspendido'];
        $usuarios = Usuario::with(['estado','estatusServicio'])
            ->whereHas('estado', function ($q) use ($downNames) {
                $q->whereIn('nombre', $downNames);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(50);
        return view('admin.usuarios_desactivados', compact('usuarios'));
    }

    public function morososIndex(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $onlyRecargo = (bool) $request->boolean('only_recargo', false);
        $data = $this->computeMorosos($month);
        $items = collect($data['items']);
        if ($onlyRecargo) {
            $items = $items->filter(fn($r) => ($r['recargo'] ?? 0) > 0);
        }
        // Ordenar por número de cliente ascendente
        $items = $items->sortBy(fn($r) => (int) ($r['numero'] ?? 0))->values();
        return view('admin.usuarios_morosos', [
            'items' => $items,
            'month' => $month,
            'onlyRecargo' => $onlyRecargo,
        ]);
    }

    public function morososExport(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'excel'));
        $month = $request->query('month', now()->format('Y-m'));
        $onlyRecargo = (bool) $request->boolean('only_recargo', false);
        $data = $this->computeMorosos($month);
        $items = collect($data['items']);
        if ($onlyRecargo) {
            $items = $items->filter(fn($r) => ($r['recargo'] ?? 0) > 0);
        }
        $title = 'Morosidad del mes '.$month;
        $fileBase = 'morosidad-'.$month.($onlyRecargo ? '-solo_recargo' : '');
        $rows = $items->map(function($r){
            return [
                $r['numero'],
                $r['nombre'],
                number_format((float)$r['mensualidad'], 2, '.', ''),
                number_format((float)$r['recargo'], 2, '.', ''),
                number_format((float)$r['pendiente'], 2, '.', ''),
                $r['vencimiento'],
                $r['dias_retraso'],
                $r['meses_adeudo'],
                $r['desde_periodo'],
            ];
        })->all();
        $total = $items->sum('pendiente');
        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$fileBase.'.csv"',
            ];
            $callback = function () use ($rows, $title, $total) {
                echo "\xEF\xBB\xBF";
                $out = fopen('php://output', 'w');
                fputcsv($out, [$title]);
                fputcsv($out, ['Número','Nombre','Mensualidad','Recargo','Pendiente','Vencimiento','Días retraso','Meses adeudo','Desde periodo']);
                foreach ($rows as $r) { fputcsv($out, $r); }
                fputcsv($out, ['', '', '', '', number_format((float)$total, 2, '.', ''), 'TOTAL', '', '', '']);
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        }
        if (in_array($format, ['excel','xls','xlsx'], true)) {
            $headers = [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$fileBase.'.xls"',
            ];
            $callback = function () use ($rows, $title, $total) {
                echo "\xEF\xBB\xBF";
                echo '<html><head><meta charset="utf-8"><style>
                table{ border-collapse:collapse; width:100%; }
                th,td{ border:1px solid #444; padding:6px 8px; font-family:Arial, Helvetica, sans-serif; font-size:11pt; }
                thead th{ background:#16a34a; color:#fff; }
                .money{ mso-number-format:"\\$#,##0.00"; text-align:right; }
                .total-row td{ background:#0f766e; color:#fff; font-weight:700; }
                </style></head><body>';
                echo '<h3>'.htmlspecialchars($title).'</h3>';
                echo '<table><thead><tr><th>Número</th><th>Nombre</th><th>Mensualidad</th><th>Recargo</th><th>Pendiente</th><th>Vencimiento</th><th>Días retraso</th><th>Meses adeudo</th><th>Desde periodo</th></tr></thead><tbody>';
                foreach ($rows as $r) {
                    echo '<tr>';
                    echo '<td>'.htmlspecialchars($r[0]).'</td>';
                    echo '<td>'.htmlspecialchars($r[1]).'</td>';
                    echo '<td class="money">'.htmlspecialchars($r[2]).'</td>';
                    echo '<td class="money">'.htmlspecialchars($r[3]).'</td>';
                    echo '<td class="money">'.htmlspecialchars($r[4]).'</td>';
                    echo '<td>'.htmlspecialchars($r[5]).'</td>';
                    echo '<td>'.htmlspecialchars($r[6]).'</td>';
                    echo '<td>'.htmlspecialchars($r[7]).'</td>';
                    echo '<td>'.htmlspecialchars($r[8]).'</td>';
                    echo '</tr>';
                }
                echo '</tbody><tfoot><tr class="total-row"><td colspan="4"></td><td class="money">'.htmlspecialchars(number_format((float)$total, 2, '.', '')).'</td><td colspan="4">TOTAL</td></tr></tfoot></table></body></html>';
            };
            return response()->stream($callback, 200, $headers);
        }
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileBase.'.pdf"',
        ];
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>
        body{ font-family:DejaVu Sans, Arial, Helvetica, sans-serif; font-size:12px; }
        h3{ margin:0 0 12px 0; }
        table{ border-collapse:collapse; width:100%; }
        th,td{ border:1px solid #444; padding:6px 8px; }
        thead th{ background:#16a34a; color:#fff; }
        td.money{ text-align:right; }
        tfoot td{ background:#0f766e; color:#fff; font-weight:bold; }</style></head><body>';
        $html .= '<h3>'.htmlspecialchars($title).'</h3>';
        $html .= '<table><thead><tr><th>Número</th><th>Nombre</th><th>Mensualidad</th><th>Recargo</th><th>Pendiente</th><th>Vencimiento</th><th>Días</th><th>Meses</th><th>Desde</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r[0]).'</td><td>'.htmlspecialchars($r[1]).'</td><td class="money">$'.htmlspecialchars($r[2]).'</td><td class="money">$'.htmlspecialchars($r[3]).'</td><td class="money">$'.htmlspecialchars($r[4]).'</td><td>'.htmlspecialchars($r[5]).'</td><td>'.htmlspecialchars($r[6]).'</td><td>'.htmlspecialchars($r[7]).'</td><td>'.htmlspecialchars($r[8]).'</td></tr>';
        }
        $html .= '</tbody><tfoot><tr><td colspan="4"></td><td class="money">$'.htmlspecialchars(number_format((float)$total, 2, '.', '')).'</td><td colspan="4">TOTAL</td></tr></tfoot></table></body></html>';
        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return response($dompdf->output(), 200, $headers);
    }

    public function prepayClientsIndex(Request $request)
    {
        $clients = Factura::whereNull('deleted_at')
            ->where(function($q) {
                $q->where('payload->prepay', 'si')
                  ->orWhere('payload->prepay', true);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($f) {
                $p = $f->payload;
                $months = (int)($p['prepay_months'] ?? 0);
                $from = $f->created_at ? Carbon::parse($f->created_at) : now();
                $to = $from->copy()->addMonths($months);
                return (object)[
                    'numero' => (string)($f->numero_servicio ?? '—'),
                    'nombre' => (string)($p['nombre'] ?? '—'),
                    'desde' => $from->format('d/m/Y'),
                    'hasta' => $to->format('d/m/Y'),
                    'monto' => (float)$f->total,
                    'created_at' => $f->created_at,
                    'meses' => $months,
                ];
            })
            ->unique('numero')
            ->values();

        // Manual pagination if needed, but for now we'll just show them all or use simple collection pagination
        $perPage = 50;
        $page = $request->get('page', 1);
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $clients->forPage($page, $perPage),
            $clients->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.pagos_adelantados', [
            'clients' => $paginatedItems
        ]);
    }
    public function metrics(Request $request)
    {
        $request->validate([
            'period' => ['nullable','in:day,week,month'],
            'date' => ['nullable','date'],
        ]);
        $period = $request->query('period', 'day');
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : now();
        $range = $this->dateRange($period, $date);

        // Aplicar recargos automáticos después del día 7
        $this->applyMonthlySurchargesIfNeeded();

        $ventasQuery = Factura::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$range['from'], $range['to']]);
        $ventasTotal = (float) $ventasQuery->sum('total');
        $ventasCount = (int) $ventasQuery->count();

        $metodos = Factura::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->get(['total', 'payload'])
            ->map(function ($f) {
                $p = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                $m = is_array($p) ? ($p['metodo'] ?? 'desconocido') : 'desconocido';
                return ['metodo' => $m, 'total' => (float) $f->total];
            })
            ->groupBy(fn($r) => $r['metodo'])
            ->map(function ($g, $k) {
                $suma = array_sum(array_map(fn($e) => (float) $e['total'], $g->all()));
                return ['metodo' => $k, 'conteo' => $g->count(), 'monto' => round($suma, 2)];
            })
            ->values();

        $ingresos = round($ventasTotal, 2);
        // Solo ventas visibles en el dashboard

        $clientesNuevos = [
            'day' => (int) Usuario::whereDate('created_at', now()->toDateString())->count(),
            'week' => (int) Usuario::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month' => (int) Usuario::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        $inventarioBajo = Inventario::whereColumn('stock', '<=', 'minimo')
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get(['id','producto','stock','minimo']);

        // Serie de tendencia de ventas según periodo
        $ventasSeries = $this->ventasSeries($period, $range);

        // Clientes con suscripción cancelada (según estatus de servicio)
        $cancelNames = ['Cancelado','Baja','Eliminado','Inactivo'];
        $canceladosQuery = Usuario::whereHas('estatusServicio', function ($q) use ($cancelNames) {
            $q->whereIn('nombre', $cancelNames);
        })->whereBetween('updated_at', [$range['from'], $range['to']]);
        $canceladosCount = (int) $canceladosQuery->count();
        $cancelados = $canceladosQuery
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get(['id','numero_servicio','nombre_cliente','updated_at']);

        $topProductos = Factura::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->get(['payload','total'])
            ->map(function ($f) {
                $p = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                $label = null;
                if (is_array($p)) {
                    $label = $p['paquete'] ?? ($p['producto'] ?? ($p['mensualidad'] ?? null));
                }
                $label = $label ?: 'General';
                return ['label' => (string)$label, 'total' => (float)$f->total];
            })
            ->groupBy(fn($r) => $r['label'])
            ->map(function ($g, $k) {
                $suma = array_sum(array_map(fn($e) => (float)$e['total'], $g->all()));
                return ['label' => $k, 'ventas' => $g->count(), 'monto' => round($suma, 2)];
            })
            ->sortByDesc('ventas')
            ->values()
            ->take(5);

        $estadoActivos = ['Activado', 'Activo'];
        $estadoDesactivos = ['Desactivado', 'Inactivo', 'Suspendido'];
        $clientesActivos = (int) Usuario::whereHas('estado', function ($q) use ($estadoActivos) {
            $q->whereIn('nombre', $estadoActivos);
        })->count();
        $clientesDesactivados = (int) Usuario::whereHas('estado', function ($q) use ($estadoDesactivos) {
            $q->whereIn('nombre', $estadoDesactivos);
        })->count();

        // Morosos (pendientes del mes seleccionado)
        $morososData = $this->computeMorosos($range['from']->format('Y-m'));
        $morososPreview = array_slice($morososData['items'], 0, 8);

        // Clientes con pagos adelantados
        $prepayClients = Factura::whereNull('deleted_at')
            ->where(function($q) {
                $q->where('payload->prepay', 'si')
                  ->orWhere('payload->prepay', true);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($f) {
                $p = $f->payload;
                $months = (int)($p['prepay_months'] ?? 0);
                $from = $f->created_at ? Carbon::parse($f->created_at) : now();
                $to = $from->copy()->addMonths($months);
                return [
                    'numero' => (string)($f->numero_servicio ?? '—'),
                    'nombre' => (string)($p['nombre'] ?? '—'),
                    'desde' => $from->format('d/m/Y'),
                    'hasta' => $to->format('d/m/Y'),
                    'monto' => (float)$f->total,
                    'created_at' => $from->toDateTimeString()
                ];
            })
            ->unique('numero')
            ->values()
            ->take(10);

        return response()->json([
            'ok' => true,
            'period' => $period,
            'from' => $range['from']->toDateTimeString(),
            'to' => $range['to']->toDateTimeString(),
            'ingresos' => $ingresos,
            'ventas_total' => $ventasTotal,
            'ventas_count' => $ventasCount,
            'metodos' => $metodos,
            'clientes_nuevos' => $clientesNuevos,
            'inventario_bajo' => $inventarioBajo,
            'ventas_series' => $ventasSeries,
            'cancelados_count' => $canceladosCount,
            'cancelados' => $cancelados,
            'top_productos' => $topProductos,
            'clientes_activos' => $clientesActivos,
            'clientes_desactivados' => $clientesDesactivados,
            'clientes_activos_label' => 'Activado',
            'morosos' => $morososPreview,
            'morosos_count' => $morososData['count'],
            'prepay_clients' => $prepayClients,
        ]);
    }

    public function corteCaja(Request $request)
    {
        $request->validate([
            'date' => ['nullable','date'],
        ]);
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : now();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();
        $ventas = Factura::whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();
        // Se omiten compras, gastos y devoluciones en el dashboard solicitado

        $totVentas = round((float) $ventas->sum('total'), 2);
        $ingresos = $totVentas;

        $metodos = $ventas
            ->map(function ($f) {
                $p = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                return $p['metodo'] ?? 'desconocido';
            })
            ->groupBy(fn($m) => $m)
            ->map(fn($g) => ['metodo' => $g->first(), 'conteo' => $g->count()])
            ->values();

        return response()->json([
            'ok' => true,
            'date' => $from->toDateString(),
            'ingresos' => $ingresos,
            'ventas' => $ventas->map(function ($f) {
                return [
                    'folio' => $f->reference_number,
                    'fecha' => optional($f->created_at)->toDateTimeString(),
                    'monto' => round((float) $f->total, 2),
                    'numero_servicio' => $f->numero_servicio,
                    'cliente' => is_array($f->payload) ? ($f->payload['nombre'] ?? null) : null,
                    'metodo' => is_array($f->payload) ? ($f->payload['metodo'] ?? null) : null,
                ];
            }),
            'metodos' => $metodos,
        ]);
    }

    private function applyMonthlySurchargesIfNeeded(): void
    {
        $now = now();
        $periodo = $now->format('Y-m');
        // Solo aplicar del día 8 en adelante, idempotente
        if ((int) $now->format('d') <= 7) {
            return;
        }
        // Obtener usuarios que no tienen pago vigente del periodo
        $pagos = Factura::whereNull('deleted_at')->where('periodo', $periodo)->pluck('numero_servicio')->filter()->unique()->all();
        $usuarios = Usuario::whereNotIn('numero_servicio', $pagos)->get(['id','numero_servicio']);
        foreach ($usuarios as $u) {
            CargoMora::firstOrCreate(
                ['numero_servicio' => (string) $u->numero_servicio, 'periodo' => $periodo],
                ['usuario_id' => $u->id, 'monto' => 50.00, 'applied_at' => now()]
            );
        }
    }

    private function computeMorosos(string $periodo): array
    {
        $usuarios = Usuario::with(['estado','estatusServicio'])->get(['id','numero_servicio','nombre_cliente','tarifa','created_at','updated_at']);
        $pagados = Factura::whereNull('deleted_at')->where('periodo', $periodo)->pluck('numero_servicio')->filter()->unique()->toArray();
        $curStart = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $dueDate = $curStart->copy()->day(7)->endOfDay();
        $today = now();
        $mora = CargoMora::where('periodo', $periodo)->pluck('monto', 'numero_servicio');
        $items = [];
        foreach ($usuarios as $u) {
            $num = (string) $u->numero_servicio;
            if (in_array($num, $pagados, true)) {
                continue; // al corriente
            }
            $mensualidad = (float) preg_replace('/[^\d.]/', '', (string) ($u->tarifa ?? 0));
            // calcular meses de adeudo basado en último pago
            $ultimoPago = Factura::whereNull('deleted_at')
                ->where('numero_servicio', $num)
                ->orderByDesc('periodo')->value('periodo');
            $mesesAdeudo = 1;
            $desdePeriodo = $periodo;
            if ($ultimoPago) {
                $lp = Carbon::createFromFormat('Y-m', $ultimoPago)->startOfMonth();
                $cur = $curStart->copy();
                // meses faltantes desde el mes posterior al último pagado hasta el periodo actual (inclusive)
                $diff = $lp->diffInMonths($cur);
                $mesesAdeudo = max(1, $diff);
                if ($lp->lessThan($cur)) {
                    $desdePeriodo = $lp->copy()->addMonth()->format('Y-m');
                }
            }
            // aplicar recargo solo una vez (primer mes de mora) y solo si ya venció (>=día 8 del periodo actual)
            $recargoUnaVez = ($today->day >= 8 && $mesesAdeudo >= 1) ? 50.0 : 0.0;
            // si existe registro en cargos_mora para el periodo actual, respetar ese monto como recargo (sin duplicar)
            if (isset($mora[$num])) {
                $recargoUnaVez = max($recargoUnaVez, (float) $mora[$num]);
            }
            // pendiente total = mensualidad * mesesAdeudo + recargoUnaVez
            $pendiente = round(($mensualidad * $mesesAdeudo) + $recargoUnaVez, 2);
            $diasRetraso = max(0, $today->diffInDays($dueDate, false) * -1);
            $items[] = [
                'numero' => $num,
                'nombre' => $u->nombre_cliente,
                'mensualidad' => $mensualidad,
                'recargo' => $recargoUnaVez,
                'pendiente' => $pendiente,
                'vencimiento' => $dueDate->toDateString(),
                'dias_retraso' => $diasRetraso,
                'meses_adeudo' => $mesesAdeudo,
                'desde_periodo' => $desdePeriodo,
                'moroso' => $pendiente > 0,
            ];
        }
        return ['count' => count($items), 'items' => $items];
    }

    public function exportResumen(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $period = (string) $request->query('period', 'day');
        if (!in_array($period, ['day','week','month'], true)) {
            $period = 'day';
        }
        if ($period === 'day') {
            $request->validate(['date' => ['required','date']]);
            $date = Carbon::parse($request->query('date'));
            $range = $this->dateRange('day', $date);
            $title = 'Resumen del día '.$date->format('d/m/Y');
            $fileBase = 'resumen-diario-'.$date->toDateString();
        } elseif ($period === 'week') {
            $request->validate([
                'from' => ['required','date'],
                'to' => ['required','date'],
            ]);
            $from = Carbon::parse($request->query('from'))->startOfDay();
            $to = Carbon::parse($request->query('to'))->endOfDay();
            if (!$from->copy()->addDays(6)->isSameDay($to)) {
                return response()->json(['ok' => false, 'message' => 'El rango de semana debe ser exactamente 7 días'], 422);
            }
            $range = ['from' => $from, 'to' => $to];
            $title = 'Resumen de la semana '.$from->format('d/m/Y').' a '.$to->format('d/m/Y');
            $fileBase = 'resumen-semanal-'.$from->toDateString().'_a_'.$to->toDateString();
        } else {
            $request->validate(['month' => ['required','date_format:Y-m']]);
            $month = Carbon::createFromFormat('Y-m', $request->query('month'));
            $range = ['from' => $month->copy()->startOfMonth(), 'to' => $month->copy()->endOfMonth()];
            $title = 'Resumen del mes '.$month->translatedFormat('F Y');
            $fileBase = 'resumen-mensual-'.$month->format('Y-m');
        }

        $ventas = Factura::whereNull('deleted_at')->whereBetween('created_at', [$range['from'], $range['to']])->get();

        $rows = [];
        $totalSum = 0.0;
        $metodosData = [];
        foreach ($ventas as $v) {
            $p = is_array($v->payload) ? $v->payload : (is_string($v->payload) ? @json_decode($v->payload, true) : []);
            $nombre = $p['nombre'] ?? '';
            $metodo = $p['metodo'] ?? 'desconocido';
            $monto = round((float)$v->total, 2);
            $totalSum += $monto;
            $rows[] = ['Venta', optional($v->created_at)->format('Y-m-d H:i'), number_format($monto, 2, '.', ''), $nombre, (string)$v->numero_servicio];

            if (!isset($metodosData[$metodo])) {
                $metodosData[$metodo] = ['cantidad' => 0, 'monto' => 0.0];
            }
            $metodosData[$metodo]['cantidad']++;
            $metodosData[$metodo]['monto'] += $monto;
        }

        $metodosRows = [];
        foreach ($metodosData as $m => $data) {
            $pct = $totalSum > 0 ? round(($data['monto'] / $totalSum) * 100, 2) : 0;
            $metodosRows[] = [
                $m,
                $data['cantidad'],
                number_format($data['monto'], 2, '.', ''),
                $pct . '%'
            ];
        }

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$fileBase.'.csv"',
            ];
            $callback = function () use ($rows, $title, $totalSum, $metodosRows) {
                echo "\xEF\xBB\xBF";
                $out = fopen('php://output', 'w');
                fputcsv($out, [$title]);
                fputcsv($out, []);
                fputcsv($out, ['DETALLE DE VENTAS']);
                fputcsv($out, ['Tipo', 'Fecha', 'Monto', 'Nombre', 'Detalle']);
                foreach ($rows as $r) {
                    fputcsv($out, $r);
                }
                fputcsv($out, ['', '', number_format($totalSum, 2, '.', ''), 'TOTAL', '']);
                
                if (!empty($metodosRows)) {
                    fputcsv($out, []);
                    fputcsv($out, ['DESGLOSE POR MÉTODO DE PAGO']);
                    fputcsv($out, ['Método', 'Cantidad', 'Monto', 'Porcentaje']);
                    foreach ($metodosRows as $mr) {
                        fputcsv($out, $mr);
                    }
                }
                
                fclose($out);
            };
            return response()->stream($callback, 200, $headers);
        }
        if (in_array($format, ['excel', 'xls', 'xlsx'], true)) {
            $headers = [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$fileBase.'.xls"',
                'Cache-Control' => 'max-age=0',
            ];
            $callback = function () use ($rows, $title, $totalSum, $metodosRows) {
                echo "\xEF\xBB\xBF";
                echo '<html><head><meta charset="utf-8"><style>
                table{ border-collapse:collapse; margin-bottom: 20px; }
                th,td{ border:1px solid #000; padding:4px 6px; font-family:Arial, Helvetica, sans-serif; font-size:10pt; }
                .hdr{ background:#16a34a; color:#fff; font-weight:bold; text-align:center; }
                .money{ mso-number-format:"\#\,\#\#0\.00"; text-align:right; }
                .total-row{ background:#0f766e; color:#fff; font-weight:bold; }
                .empty-cell{ border:1px solid #000; }
                </style></head><body>';
                echo '<h3>'.htmlspecialchars($title).'</h3>';
                
                echo '<table>';
                // Encabezados principales
                echo '<thead>';
                echo '<tr>';
                echo '<th colspan="5" class="hdr">DETALLE DE VENTAS</th>';
                echo '<th colspan="4" class="hdr">DESGLOSE POR MÉTODO DE PAGO</th>';
                echo '</tr>';
                echo '<tr>';
                echo '<th class="hdr">Tipo</th><th class="hdr">Fecha</th><th class="hdr">Monto</th><th class="hdr">Nombre</th><th class="hdr">Detalle</th>';
                echo '<th class="hdr">Método</th><th class="hdr">Cantidad</th><th class="hdr">Monto</th><th class="hdr">Porcentaje</th>';
                echo '</tr>';
                echo '</thead>';

                echo '<tbody>';
                $detalleCount = count($rows);
                $metodoCount = count($metodosRows);
                $maxRows = max($detalleCount + 1, $metodoCount); // +1 para la fila de TOTAL

                for ($i = 0; $i < $maxRows; $i++) {
                    echo '<tr>';
                    
                    // Lado Izquierdo: Detalle de Ventas
                    if ($i < $detalleCount) {
                        $r = $rows[$i];
                        echo '<td>'.htmlspecialchars($r[0]).'</td>';
                        echo '<td>'.htmlspecialchars($r[1]).'</td>';
                        echo '<td class="money">'.htmlspecialchars($r[2]).'</td>';
                        echo '<td>'.htmlspecialchars($r[3]).'</td>';
                        echo '<td>'.htmlspecialchars($r[4]).'</td>';
                    } elseif ($i === $detalleCount) {
                        echo '<td colspan="2" class="total-row">TOTAL</td>';
                        echo '<td class="total-row money">'.htmlspecialchars(number_format($totalSum, 2, '.', '')).'</td>';
                        echo '<td colspan="2" class="total-row"></td>';
                    } else {
                        echo '<td class="empty-cell"></td><td class="empty-cell"></td><td class="empty-cell"></td><td class="empty-cell"></td><td class="empty-cell"></td>';
                    }

                    // Lado Derecho: Desglose
                    if ($i < $metodoCount) {
                        $mr = $metodosRows[$i];
                        echo '<td>'.htmlspecialchars($mr[0]).'</td>';
                        echo '<td style="text-align:center">'.htmlspecialchars($mr[1]).'</td>';
                        echo '<td class="money">'.htmlspecialchars($mr[2]).'</td>';
                        echo '<td style="text-align:center">'.htmlspecialchars($mr[3]).'</td>';
                    } else {
                        echo '<td class="empty-cell"></td><td class="empty-cell"></td><td class="empty-cell"></td><td class="empty-cell"></td>';
                    }

                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';

                echo '</body></html>';
            };
            return response()->stream($callback, 200, $headers);
        }
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileBase.'.pdf"',
        ];
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title>
        <style>
        body{ font-family:DejaVu Sans, Arial, Helvetica, sans-serif; font-size:12px; }
        h3{ margin:0 0 12px 0; }
        table{ border-collapse:collapse; width:100%; }
        th,td{ border:1px solid #444; padding:6px 8px; }
        thead th{ background:#16a34a; color:#fff; }
        td.money{ text-align:right; }
        tfoot td{ background:#0f766e; color:#fff; font-weight:bold; }
        </style></head><body>';
        $html .= '<h3>'.htmlspecialchars($title).'</h3>';
        $html .= '<h4>DETALLE DE VENTAS</h4>';
        $html .= '<table><thead><tr><th>Tipo</th><th>Fecha</th><th>Monto</th><th>Nombre</th><th>Detalle</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>'.htmlspecialchars($r[0]).'</td><td>'.htmlspecialchars($r[1]).'</td><td class="money">$'.htmlspecialchars($r[2]).'</td><td>'.htmlspecialchars($r[3]).'</td><td>'.htmlspecialchars($r[4]).'</td></tr>';
        }
        $html .= '</tbody><tfoot><tr><td></td><td>TOTAL</td><td class="money">$'.htmlspecialchars(number_format($totalSum, 2, '.', '')).'</td><td colspan="2"></td></tr></tfoot></table>';

        if (!empty($metodosRows)) {
            $html .= '<br><h4>DESGLOSE POR MÉTODO DE PAGO</h4>';
            $html .= '<table><thead><tr><th>Método</th><th>Cantidad</th><th>Monto</th><th>Porcentaje</th></tr></thead><tbody>';
            foreach ($metodosRows as $mr) {
                $html .= '<tr><td>'.htmlspecialchars($mr[0]).'</td><td style="text-align:center">'.htmlspecialchars($mr[1]).'</td><td class="money">$'.htmlspecialchars($mr[2]).'</td><td style="text-align:center">'.htmlspecialchars($mr[3]).'</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();
        return response($pdfOutput, 200, $headers);
    }

    private function dateRange(string $period, Carbon $date): array
    {
        if ($period === 'week') {
            return ['from' => $date->copy()->startOfWeek(), 'to' => $date->copy()->endOfWeek()];
        }
        if ($period === 'month') {
            return ['from' => $date->copy()->startOfMonth(), 'to' => $date->copy()->endOfMonth()];
        }
        if ($period === 'year') {
            return ['from' => $date->copy()->startOfYear(), 'to' => $date->copy()->endOfYear()];
        }
        return ['from' => $date->copy()->startOfDay(), 'to' => $date->copy()->endOfDay()];
    }

    private function ventasSeries(string $period, array $range): array
    {
        $ventas = Factura::whereNull('deleted_at')
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->get(['created_at','total']);
        $buckets = [];
        if ($period === 'week' || $period === 'day') {
            // Agrupar por día
            $cursor = $range['from']->copy()->startOfDay();
            while ($cursor <= $range['to']) {
                $key = $cursor->format('Y-m-d');
                $buckets[$key] = 0.0;
                $cursor->addDay();
            }
            foreach ($ventas as $v) {
                $k = optional($v->created_at)->format('Y-m-d');
                if ($k && array_key_exists($k, $buckets)) {
                    $buckets[$k] += (float) $v->total;
                }
            }
            return [
                'labels' => array_keys($buckets),
                'values' => array_map(fn($x) => round((float) $x, 2), array_values($buckets)),
            ];
        }
        if ($period === 'month') {
            // Agrupar por día del mes
            $daysInMonth = (int) $range['from']->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $key = $range['from']->copy()->day($d)->format('Y-m-d');
                $buckets[$key] = 0.0;
            }
            foreach ($ventas as $v) {
                $k = optional($v->created_at)->format('Y-m-d');
                if ($k && array_key_exists($k, $buckets)) {
                    $buckets[$k] += (float) $v->total;
                }
            }
            return [
                'labels' => array_keys($buckets),
                'values' => array_map(fn($x) => round((float) $x, 2), array_values($buckets)),
            ];
        }
        if ($period === 'year') {
            // Agrupar por mes
            for ($m = 1; $m <= 12; $m++) {
                $key = $range['from']->copy()->month($m)->format('Y-m');
                $buckets[$key] = 0.0;
            }
            foreach ($ventas as $v) {
                $k = optional($v->created_at)->format('Y-m');
                if ($k && array_key_exists($k, $buckets)) {
                    $buckets[$k] += (float) $v->total;
                }
            }
            return [
                'labels' => array_keys($buckets),
                'values' => array_map(fn($x) => round((float) $x, 2), array_values($buckets)),
            ];
        }
        return ['labels' => [], 'values' => []];
    }
}
