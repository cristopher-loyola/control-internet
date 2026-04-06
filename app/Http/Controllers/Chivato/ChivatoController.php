<?php

namespace App\Http\Controllers\Chivato;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Factura;
use App\Models\Usuario;
use App\Services\MorosidadService;
use App\Services\PrepayDashboardService;
use App\Services\ZonaDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChivatoController extends Controller
{
    public function index(Request $request)
    {
        $zona = 'Chivato';

        return view('chivato.index', [
            'zona' => $zona,
            'stats' => ZonaDashboardService::stats($zona),
            'chart' => ZonaDashboardService::chartNewClientsLast7Days($zona),
            'payments' => ZonaDashboardService::recentPayments($zona, 10),
        ]);
    }

    public function pagos(Request $request)
    {
        return view('chivato.pagos');
    }

    public function corte(Request $request)
    {
        // Obtener pagos exitosos (no cancelados) del perfil chivato
        $pagos = Factura::whereNull('deleted_at')
            ->whereNotNull('numero_servicio')
            ->whereHas('cajero', function ($q) {
                $q->where('role', 'chivato');
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'reference_number', 'numero_servicio', 'periodo', 'total', 'payload', 'created_at']);

        $items = $pagos->map(function ($f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);

            return [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'periodo' => $f->periodo,
                'total' => (float) $f->total,
                'metodo' => $payload['metodo'] ?? ($payload['pago_metodo'] ?? '-'),
                'cobro' => $payload['cobro'] ?? '-',
                'nombre' => $payload['nombre'] ?? '-',
                'fecha' => $f->created_at ? $f->created_at->toDateTimeString() : null,
                'fecha_formateada' => $f->created_at ? $f->created_at->format('d/m/Y H:i') : null,
            ];
        });

        return view('chivato.corte', ['pagos' => $items]);
    }

    public function historial(Request $request)
    {
        $pagos = Factura::withTrashed()
            ->whereNotNull('numero_servicio')
            ->whereHas('cajero', function ($q) {
                $q->where('role', 'chivato');
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'reference_number', 'numero_servicio', 'periodo', 'total', 'payload', 'created_at', 'deleted_at']);

        $items = $pagos->map(function ($f) {
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
            $status = $f->deleted_at ? 'cancelado' : 'pagado';

            return [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'periodo' => $f->periodo,
                'total' => (float) $f->total,
                'metodo' => $payload['metodo'] ?? ($payload['pago_metodo'] ?? '-'),
                'cobro' => $payload['cobro'] ?? '-',
                'nombre' => $payload['nombre'] ?? '-',
                'fecha' => $f->created_at ? $f->created_at->toDateTimeString() : null,
                'fecha_formateada' => $f->created_at ? $f->created_at->format('d/m/Y H:i') : null,
                'status' => $status,
                'cancelado' => $f->deleted_at !== null,
            ];
        });

        return view('chivato.historial', ['pagos' => $items]);
    }

    public function eliminarPago(Request $request, $id)
    {
        $factura = Factura::findOrFail($id);
        
        // Verificar que el pago pertenece a un cajero del perfil chivato
        $cajero = \App\Models\User::find($factura->created_by);
        if (!$cajero || $cajero->role !== 'chivato') {
            return redirect()->route('chivato.historial')->with('error', 'No tienes permiso para eliminar este pago.');
        }
        
        // Soft delete
        $factura->delete();
        
        return redirect()->route('chivato.historial')->with('success', 'Pago eliminado correctamente.');
    }

    // API Functions for Chivato Payments (Independent)

    public function prepaySettings(Request $request)
    {
        $rows = \App\Models\PrepaySetting::all()->pluck('enabled', 'paquete')->toArray();
        $defaults = [300 => true, 400 => true, 500 => true, 600 => true];
        $enabled = array_merge($defaults, $rows);
        $matrix = [
            6 => ['percent' => 10, 'totals' => [300 => 1620, 400 => 2160, 500 => 2700, 600 => 3240]],
            7 => ['percent' => 11, 'totals' => [300 => 1869, 400 => 2492, 500 => 3115, 600 => 3738]],
            8 => ['percent' => 12, 'totals' => [300 => 2112, 400 => 2816, 500 => 3520, 600 => 4224]],
            9 => ['percent' => 13, 'totals' => [300 => 2349, 400 => 3132, 500 => 3915, 600 => 4698]],
            10 => ['percent' => 14, 'totals' => [300 => 2580, 400 => 3440, 500 => 4300, 600 => 5160]],
            11 => ['percent' => 15, 'totals' => [300 => 2805, 400 => 3740, 500 => 4675, 600 => 5610]],
            12 => ['percent' => 16, 'totals' => [300 => 3024, 400 => 4032, 500 => 5040, 600 => 6048]],
        ];

        return response()->json(['ok' => true, 'enabled' => $enabled, 'matrix' => $matrix]);
    }

    public function recibosLookup(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $u = Usuario::with(['estado', 'estatusServicio'])->where('numero_servicio', $numero)->first();
        if (! $u) {
            return response()->json(['ok' => false, 'message' => 'No encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'numero_servicio' => $u->numero_servicio,
                'nombre_cliente' => $u->nombre_cliente,
                'domicilio' => $u->domicilio,
                'telefono' => $u->telefono,
                'paquete' => $u->paquete,
                'tarifa' => $u->tarifa,
                'uso' => $u->uso,
                'tecnologia' => $u->tecnologia,
                'megas' => $u->megas,
                'estado' => optional($u->estado)->nombre,
                'estatus' => optional($u->estatusServicio)->nombre,
            ],
        ]);
    }

    public function recibosPagoAnterior(Request $request)
    {
        $numero = (string) $request->query('numero');
        $excludeId = $request->query('exclude_id');

        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }

        $f = Factura::where('numero_servicio', $numero)
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Sin pagos anteriores'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'monto' => (float) $f->total,
                'fecha' => $f->created_at->toIso8601String(),
                'created_at' => $f->created_at->toIso8601String(),
                'reference_number' => $f->reference_number,
            ],
        ]);
    }

    public function recibosDeuda(Request $request, MorosidadService $service)
    {
        $numero = (string) $request->query('numero');
        $month = $request->query('month');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        if ($month !== null && ! preg_match('/^\d{4}\-\d{2}$/', (string) $month)) {
            $month = null;
        }
        $res = $service->calcularAdeudoUsuario($numero, $month);
        if (! ($res['ok'] ?? false)) {
            return response()->json($res, 404);
        }

        return response()->json($res);
    }

    public function recibosPrepayStatus(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $row = Factura::whereNull('deleted_at')
            ->where('numero_servicio', $numero)
            ->where(function ($q) {
                $q->where('payload->prepay', 'si')
                    ->orWhere('payload->prepay', true);
            })
            ->orderByDesc('id')
            ->first(['id', 'numero_servicio', 'total', 'payload', 'created_at']);
        if (! $row) {
            return response()->json(['ok' => true, 'data' => ['activo' => false]]);
        }
        $p = is_array($row->payload) ? $row->payload : (is_string($row->payload) ? @json_decode($row->payload, true) : []);
        $months = (int) (($p['prepay_months'] ?? 0) ?: 0);
        $from = $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at) : null;
        $venceAt = PrepayDashboardService::venceAt($from, $months);
        $estado = PrepayDashboardService::estadoPorVencimiento($venceAt, now());
        $label = $venceAt ? $venceAt->locale('es')->translatedFormat('F Y') : null;

        return response()->json([
            'ok' => true,
            'data' => [
                'activo' => ! $estado['vencido'] && $venceAt !== null,
                'hasta_label' => $label,
                'hasta_periodo' => $venceAt ? $venceAt->format('Y-m') : null,
                'meses' => $months,
            ],
        ]);
    }

    public function recibosLayoutGet()
    {
        $setting = AppSetting::find('receipt_layout');

        return response()->json([
            'ok' => true,
            'layout' => $setting ? $setting->value : null,
        ]);
    }

    public function recibosFacturaStore(Request $request)
    {
        $request->validate([
            'numero_servicio' => ['nullable', 'string'],
            'usuario_id' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric'],
            'payload' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($request) {
            $periodo = now()->format('Y-m');
            $numero = $request->input('numero_servicio');
            $usuarioId = $request->input('usuario_id');
            if ($numero) {
                $prepay = Factura::whereNull('deleted_at')
                    ->where('numero_servicio', $numero)
                    ->where(function ($q) {
                        $q->where('payload->prepay', 'si')
                            ->orWhere('payload->prepay', true);
                    })
                    ->orderByDesc('id')
                    ->first(['id', 'payload', 'created_at']);
                if ($prepay) {
                    $p = is_array($prepay->payload) ? $prepay->payload : (is_string($prepay->payload) ? @json_decode($prepay->payload, true) : []);
                    $months = (int) (($p['prepay_months'] ?? 0) ?: 0);
                    $from = $prepay->created_at ? \Illuminate\Support\Carbon::parse($prepay->created_at) : null;
                    $venceAt = PrepayDashboardService::venceAt($from, $months);
                    $estado = PrepayDashboardService::estadoPorVencimiento($venceAt, now());
                    if ($venceAt && ! $estado['vencido']) {
                        return response()->json([
                            'ok' => false,
                            'message' => 'Pago adelantado vigente hasta '.$venceAt->locale('es')->translatedFormat('F Y'),
                        ], 409);
                    }
                }
            }
            $row = DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->lockForUpdate()
                ->first();
            if (! $row) {
                DB::table('invoice_sequences')->insert([
                    'name' => 'facturas',
                    'current_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = (object) ['current_value' => 0];
            }
            $payload = $request->input('payload', []);
            $fingerprintData = [
                'numero_servicio' => $request->input('numero_servicio'),
                'periodo' => $periodo,
                'total' => round((float) $request->input('total', 0), 2),
                'nombre' => $payload['nombre'] ?? null,
                'mensualidad' => $payload['mensualidad'] ?? null,
                'recargo' => $payload['recargo'] ?? null,
                'pago_anterior' => $payload['pago_anterior'] ?? null,
                'metodo' => $payload['metodo'] ?? 'Efectivo',
            ];
            $fingerprint = hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $existing = Factura::where(function ($q) use ($fingerprint) {
                $q->where('fingerprint', $fingerprint);
            })
                ->orWhere(function ($q) use ($request, $periodo) {
                    $q->where('numero_servicio', $request->input('numero_servicio'))
                        ->where('periodo', $periodo)
                        ->where('total', $request->input('total', 0))
                        ->whereRaw('payload = ?', [json_encode($request->input('payload', []))]);
                })
                ->orderBy('id', 'desc')
                ->first();
            if ($existing) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'success',
                    'reason' => 'Reimpresión / reuso de factura',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => true,
                    'referencia' => $existing->reference_number,
                    'id' => $existing->id,
                    'reused' => true,
                ]);
            }

            $trashedByFingerprint = Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
            if ($trashedByFingerprint) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'success',
                    'reason' => 'Reimpresión / reuso de factura (trashed)',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => true,
                    'referencia' => $trashedByFingerprint->reference_number,
                    'id' => $trashedByFingerprint->id,
                    'reused' => true,
                ]);
            }

            if (($numero !== null && $numero !== '') || ! empty($usuarioId)) {
                $dup = Factura::where('periodo', $periodo)
                    ->where(function ($q) use ($numero, $usuarioId) {
                        if ($numero !== null && $numero !== '') {
                            $q->where('numero_servicio', $numero);
                        }
                        if (! empty($usuarioId)) {
                            $q->orWhere('usuario_id', $usuarioId);
                        }
                    })
                    ->first();
            } else {
                $dup = null;
            }
            if ($dup) {
                DB::table('payment_attempts')->insert([
                    'usuario_id' => $usuarioId,
                    'numero_servicio' => $numero,
                    'periodo' => $periodo,
                    'status' => 'duplicate',
                    'reason' => 'Pago ya registrado para este periodo',
                    'created_by' => optional($request->user())->id,
                    'payload' => json_encode($request->input('payload', [])),
                    'attempted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'Ya existe un pago registrado para este periodo',
                    'referencia' => $dup->reference_number,
                    'periodo' => $periodo,
                ], 409);
            }
            $next = (int) $row->current_value + 1;
            DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->update(['current_value' => $next, 'updated_at' => now()]);

            try {
                $factura = new Factura;
                $factura->reference_number = $next;
                $factura->usuario_id = $request->input('usuario_id');
                $factura->numero_servicio = $request->input('numero_servicio');
                $factura->periodo = $periodo;
                $factura->total = $request->input('total', 0);
                $factura->payload = $payload;
                $factura->created_by = $request->user()?->id;
                $factura->fingerprint = $fingerprint;
                $factura->save();

                if ($request->input('usuario_id')) {
                    $usuario = Usuario::find($request->input('usuario_id'));
                    if ($usuario) {
                        $usuario->update([
                            'estatus_servicio_id' => 1,
                            'estado_id' => 1,
                        ]);
                    }
                } elseif ($request->input('numero_servicio')) {
                    $usuario = Usuario::where('numero_servicio', $request->input('numero_servicio'))->first();
                    if ($usuario) {
                        $usuario->update([
                            'estatus_servicio_id' => 1,
                            'estado_id' => 1,
                        ]);
                    }
                }
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $c = Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
                if ($c) {
                    return response()->json([
                        'ok' => true,
                        'referencia' => $c->reference_number,
                        'id' => $c->id,
                        'reused' => true,
                    ]);
                }
                throw $e;
            }

            DB::table('payment_attempts')->insert([
                'usuario_id' => $usuarioId,
                'numero_servicio' => $numero,
                'periodo' => $periodo,
                'status' => 'success',
                'reason' => 'Factura creada',
                'created_by' => optional($request->user())->id,
                'payload' => json_encode($request->input('payload', [])),
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'ok' => true,
                'referencia' => $factura->reference_number,
                'id' => $factura->id,
            ]);
        });
    }
}
