<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\HistorialUsuario;
use App\Models\Usuario;
use App\Services\MegasAssigner;
use App\Services\MorosidadService;
use App\Services\PrepayDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function pagosPagoAnterior(Request $request)
    {
        $numero = (string) $request->query('numero');
        $excludeId = $request->query('exclude_id');

        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }

        $f = \App\Models\Factura::where('numero_servicio', $numero)
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

    public function pagosDeuda(Request $request, MorosidadService $service)
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

    public function pagos()
    {
        return view('admin.pagos');
    }

    public function pagosLayoutStore(Request $request)
    {
        $layout = $request->input('layout');
        if (! is_array($layout)) {
            return response()->json(['ok' => false, 'message' => 'Layout inválido'], 422);
        }

        AppSetting::updateOrCreate(
            ['key' => 'receipt_layout'],
            ['value' => $layout]
        );

        return response()->json(['ok' => true]);
    }

    public function pagosLayoutGet()
    {
        $setting = AppSetting::find('receipt_layout');

        return response()->json([
            'ok' => true,
            'layout' => $setting ? $setting->value : null,
        ]);
    }

    public function pagosPrepayStatus(\Illuminate\Http\Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $row = \App\Models\Factura::whereNull('deleted_at')
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

    public function pagosFacturaStore(\Illuminate\Http\Request $request, MorosidadService $service)
    {
        $request->validate([
            'numero_servicio' => ['nullable', 'string'],
            'usuario_id' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric'],
            'payload' => ['nullable', 'array'],
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $service) {
            $periodo = now()->format('Y-m');
            $numero = $request->input('numero_servicio');
            $usuarioId = $request->input('usuario_id');
            $payload = $request->input('payload', []);
            $isBajaTemporal = ($payload['otro'] ?? null) === 'baja_temporal';

            if ($numero && ! $isBajaTemporal) {
                $prepay = \App\Models\Factura::whereNull('deleted_at')
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
            $row = \Illuminate\Support\Facades\DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->lockForUpdate()
                ->first();
            if (! $row) {
                \Illuminate\Support\Facades\DB::table('invoice_sequences')->insert([
                    'name' => 'facturas',
                    'current_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = (object) ['current_value' => 0];
            }
            $total = round((float) $request->input('total', 0), 2);

            if (($payload['otro'] ?? null) === 'baja_temporal') {
                if (! $numero || ! ctype_digit((string) $numero)) {
                    return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
                }

                $adeudo = $service->calcularAdeudoUsuario((string) $numero, null);
                if (! ($adeudo['ok'] ?? false)) {
                    return response()->json(['ok' => false, 'message' => $adeudo['message'] ?? 'No se pudo validar adeudos'], 409);
                }
                $adeudoPendiente = round((float) ($adeudo['pendiente'] ?? 0), 2);

                $months = (int) ($payload['baja_temporal_months'] ?? 0);
                if ($months < 1 || $months > 6) {
                    return response()->json(['ok' => false, 'message' => 'Meses de baja temporal inválidos (1–6)'], 422);
                }

                $mensualidad = 0.0;
                $u = ! empty($usuarioId)
                    ? Usuario::find($usuarioId)
                    : Usuario::where('numero_servicio', (string) $numero)->first();
                if ($u && $u->tarifa !== null) {
                    $mensualidad = (float) $u->tarifa;
                }
                if ($mensualidad <= 0) {
                    $mensualidad = (float) preg_replace('/[^\d.]/', '', (string) ($payload['mensualidad'] ?? 0));
                }
                if ($mensualidad <= 0) {
                    return response()->json(['ok' => false, 'message' => 'No se pudo determinar la mensualidad para calcular la baja temporal'], 422);
                }

                $bajaTotal = round($mensualidad * 0.2 * $months, 2);
                $payload['mensualidad'] = $mensualidad;
                $payload['otro'] = 'baja_temporal';
                $payload['recargo'] = 'no';
                $payload['prepay'] = 'no';
                $payload['prepay_months'] = null;
                $payload['prepay_total'] = null;
                $payload['adeudo_pendiente'] = $adeudoPendiente;
                $payload['baja_temporal_months'] = $months;
                $payload['baja_temporal_total'] = $bajaTotal;
                $payload['adeudo_prev'] = $adeudoPendiente;
                $payload['adeudo_nuevo'] = round($adeudoPendiente + $bajaTotal, 2);

                $descuento = round((float) ($payload['descuento'] ?? 0), 2);
                $total = round(max(0, $adeudoPendiente + $bajaTotal - $descuento), 2);
            }

            $manualOverride = null;
            if (($payload['manual_total_enabled'] ?? false) && (($payload['otro'] ?? null) !== 'baja_temporal')) {
                $reason = trim((string) ($payload['manual_total_reason'] ?? ''));
                if ($reason === '') {
                    return response()->json(['ok' => false, 'message' => 'Motivo requerido para editar el total'], 422);
                }
                $manualValue = $payload['manual_total_value'] ?? null;
                if (! is_numeric($manualValue)) {
                    return response()->json(['ok' => false, 'message' => 'Total manual inválido'], 422);
                }
                $manualValue = round((float) $manualValue, 2);
                if ($manualValue < 0) {
                    return response()->json(['ok' => false, 'message' => 'Total manual inválido'], 422);
                }
                $manualOverride = [
                    'prev_total' => $total,
                    'new_total' => $manualValue,
                    'reason' => mb_substr($reason, 0, 250),
                ];
                $total = $manualValue;
                $payload['manual_total_enabled'] = true;
                $payload['manual_total_value'] = $manualValue;
                $payload['manual_total_reason'] = $manualOverride['reason'];
            }

            $periodoFactura = $isBajaTemporal ? null : $periodo;
            $payloadJson = json_encode($payload);
            $fingerprintData = [
                'numero_servicio' => $request->input('numero_servicio'),
                'periodo' => $periodoFactura,
                'total' => $total,
                'nombre' => $payload['nombre'] ?? null,
                'mensualidad' => $payload['mensualidad'] ?? null,
                'recargo' => $payload['recargo'] ?? null,
                'pago_anterior' => $payload['pago_anterior'] ?? null,
                'metodo' => $payload['metodo'] ?? 'Efectivo',
            ];
            $fingerprint = $isBajaTemporal ? null : hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if (! $isBajaTemporal) {
                $existing = \App\Models\Factura::where(function ($q) use ($fingerprint) {
                    $q->where('fingerprint', $fingerprint);
                })
                    ->orWhere(function ($q) use ($request, $periodoFactura, $total, $payloadJson) {
                        $q->where('numero_servicio', $request->input('numero_servicio'))
                            ->where('periodo', $periodoFactura)
                            ->where('total', $total)
                            ->whereRaw('payload = ?', [$payloadJson]);
                    })
                    ->orderBy('id', 'desc')
                    ->first();
                if ($existing) {
                    \Illuminate\Support\Facades\DB::table('payment_attempts')->insert([
                        'usuario_id' => $usuarioId,
                        'numero_servicio' => $numero,
                        'periodo' => $periodoFactura,
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

                $trashedByFingerprint = \App\Models\Factura::withTrashed()->where('fingerprint', $fingerprint)->first();
                if ($trashedByFingerprint) {
                    \Illuminate\Support\Facades\DB::table('payment_attempts')->insert([
                        'usuario_id' => $usuarioId,
                        'numero_servicio' => $numero,
                        'periodo' => $periodoFactura,
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

                // Validación de duplicados por periodo solo si no es reimpresión
                if (($numero !== null && $numero !== '') || ! empty($usuarioId)) {
                    $dup = \App\Models\Factura::where('periodo', $periodoFactura)
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
                    \Illuminate\Support\Facades\DB::table('payment_attempts')->insert([
                        'usuario_id' => $usuarioId,
                        'numero_servicio' => $numero,
                        'periodo' => $periodoFactura,
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
                        'periodo' => $periodoFactura,
                    ], 409);
                }
            }
            $next = (int) $row->current_value + 1;
            \Illuminate\Support\Facades\DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->update(['current_value' => $next, 'updated_at' => now()]);

            try {
                $factura = new \App\Models\Factura;
                $factura->reference_number = $next;
                $factura->usuario_id = $request->input('usuario_id');
                $factura->numero_servicio = $request->input('numero_servicio');
                $factura->periodo = $periodoFactura;
                $factura->total = $total;
                $factura->payload = $payload;
                $factura->created_by = $request->user()?->id;
                $factura->fingerprint = $fingerprint;
                $factura->save();

                if ($manualOverride) {
                    \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                        'actor_user_id' => $request->user()?->id,
                        'actor_role' => $request->user()?->role,
                        'actor_name' => $request->user()?->name,
                        'action' => 'factura_total_override',
                        'table_name' => 'facturas',
                        'entity_type' => \App\Models\Factura::class,
                        'entity_id' => (string) $factura->id,
                        'prev_values' => json_encode(['total' => $manualOverride['prev_total']]),
                        'new_values' => json_encode(['total' => $manualOverride['new_total'], 'reason' => $manualOverride['reason']]),
                        'ip' => $request->ip(),
                        'user_agent' => (string) $request->userAgent(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Al registrar un pago exitoso, actualizar el estatus del cliente a Pagado/Activado
                if (! $isBajaTemporal) {
                    if ($request->input('usuario_id')) {
                        $usuario = \App\Models\Usuario::find($request->input('usuario_id'));
                        if ($usuario) {
                            $usuario->update([
                                'estatus_servicio_id' => 1, // Pagado
                                'estado_id' => 1,           // Activado
                                'adeudo_monto' => 0,
                                'adeudo_descripcion' => null,
                            ]);
                        }
                    } elseif ($request->input('numero_servicio')) {
                        $usuario = \App\Models\Usuario::where('numero_servicio', $request->input('numero_servicio'))->first();
                        if ($usuario) {
                            $usuario->update([
                                'estatus_servicio_id' => 1, // Pagado
                                'estado_id' => 1,           // Activado
                                'adeudo_monto' => 0,
                                'adeudo_descripcion' => null,
                            ]);
                        }
                    }
                } else {
                    $baja = \App\Models\EstatusServicio::whereRaw('LOWER(nombre) = ?', ['baja temporal'])->first();
                    if (! $baja) {
                        $baja = \App\Models\EstatusServicio::create(['nombre' => 'Baja temporal']);
                    }

                    if ($request->input('usuario_id')) {
                        $usuario = \App\Models\Usuario::find($request->input('usuario_id'));
                    } else {
                        $usuario = \App\Models\Usuario::where('numero_servicio', $request->input('numero_servicio'))->first();
                    }

                    if ($usuario) {
                        $prev = [
                            'estatus_servicio_id' => $usuario->estatus_servicio_id,
                            'adeudo_monto' => $usuario->adeudo_monto,
                            'adeudo_descripcion' => $usuario->adeudo_descripcion,
                        ];

                        $usuario->update([
                            'estatus_servicio_id' => $baja->id,
                            'adeudo_monto' => 0,
                            'adeudo_descripcion' => null,
                        ]);

                        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                            'actor_user_id' => $request->user()?->id,
                            'actor_role' => $request->user()?->role,
                            'actor_name' => $request->user()?->name,
                            'action' => 'usuario_baja_temporal',
                            'table_name' => 'usuarios',
                            'entity_type' => \App\Models\Usuario::class,
                            'entity_id' => (string) $usuario->id,
                            'prev_values' => json_encode($prev),
                            'new_values' => json_encode(['estatus_servicio_id' => $baja->id, 'adeudo_monto' => 0, 'adeudo_descripcion' => null]),
                            'ip' => $request->ip(),
                            'user_agent' => (string) $request->userAgent(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (\Illuminate\Database\QueryException $e) {
                // Si falla por fingerprint duplicado (carrera), buscamos el que ganó
                // Pero solo si NO está cancelado. Si está cancelado, dejamos que falle
                $c = $fingerprint ? \App\Models\Factura::withTrashed()->where('fingerprint', $fingerprint)->first() : null;
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

            \Illuminate\Support\Facades\DB::table('payment_attempts')->insert([
                'usuario_id' => $usuarioId,
                'numero_servicio' => $numero,
                'periodo' => $periodoFactura,
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

    public function pagosFacturasIndex(\Illuminate\Http\Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $rows = \App\Models\Factura::orderByDesc('id')->limit($limit)->get([
            'id', 'reference_number', 'numero_servicio', 'total', 'created_at',
        ]);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function pagosFacturaShow(int $id)
    {
        $f = \App\Models\Factura::findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'total' => $f->total,
                'payload' => $f->payload,
                'created_at' => $f->created_at,
            ],
        ]);
    }

    public function pagosFacturaByFolio(int $ref)
    {
        $f = \App\Models\Factura::where('reference_number', $ref)->first();
        if (! $f) {
            return response()->json(['ok' => false, 'message' => 'Folio no encontrado'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'total' => $f->total,
                'payload' => $f->payload,
                'created_at' => $f->created_at,
            ],
        ]);
    }

    public function pagosHistorial(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $cliente = trim((string) $request->query('cliente', ''));
        $perPage = 50;
        $query = \App\Models\Factura::withTrashed()
            ->orderByDesc('id');
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($cliente !== '') {
            $query->where(function ($q) use ($cliente) {
                if (ctype_digit($cliente)) {
                    $q->where('numero_servicio', $cliente);
                } else {
                    $q->orWhereRaw("JSON_EXTRACT(payload, '$.nombre') LIKE ?", ['%'.$cliente.'%']);
                }
            });
        }
        $paginator = $query->paginate($perPage)->appends($request->query());
        $ids = $paginator->getCollection()->pluck('created_by')->filter()->unique()->all();
        $users = \App\Models\User::whereIn('id', $ids)->get(['id', 'name'])->keyBy('id');

        // Obtener los motivos de cancelación
        $reasonsRaw = \Illuminate\Support\Facades\DB::table('payment_attempts')
            ->select('numero_servicio', 'periodo', 'reason')
            ->where('status', 'canceled')
            ->whereIn('numero_servicio', $paginator->getCollection()->pluck('numero_servicio')->filter()->unique()->all())
            ->get();
        $reasons = [];
        foreach ($reasonsRaw as $r) {
            $reasons[$r->numero_servicio.'|'.$r->periodo] = $r->reason;
        }

        $rows = $paginator->getCollection()->map(function ($f) use ($users, $reasons) {
            $nombre = null;
            $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
            if (is_array($payload) && array_key_exists('nombre', $payload)) {
                $nombre = $payload['nombre'];
            }

            return (object) [
                'id' => $f->id,
                'reference_number' => $f->reference_number,
                'numero_servicio' => $f->numero_servicio,
                'total' => $f->total,
                'cliente' => $nombre,
                'created_at' => $f->created_at,
                'deleted_at' => $f->deleted_at,
                'status' => $f->deleted_at ? 'Cancelado' : 'Vigente',
                'user_name' => optional($users->get($f->created_by))->name,
                'motivo_cancelacion' => $reasons[($f->numero_servicio ?? '').'|'.($f->periodo ?? '')] ?? null,
                'descuento' => $payload['descuento'] ?? 0,
            ];
        });

        return view('admin.pagos_historial', [
            'rows' => $rows,
            'paginator' => $paginator,
            'from' => $from,
            'to' => $to,
            'cliente' => $cliente,
        ]);
    }

    public function pagosHistorialExport(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $from = $request->query('from');
        $to = $request->query('to');
        $cliente = trim((string) $request->query('cliente', ''));
        $query = \App\Models\Factura::withTrashed()->orderByDesc('id');
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($cliente !== '') {
            $query->where(function ($q) use ($cliente) {
                if (ctype_digit($cliente)) {
                    $q->where('numero_servicio', $cliente);
                } else {
                    $q->orWhereRaw("JSON_EXTRACT(payload, '$.nombre') LIKE ?", ['%'.$cliente.'%']);
                }
            });
        }
        $items = $query->get();
        $totalRecaudado = $items->filter(fn ($f) => $f->deleted_at === null)->sum('total');
        // Prefetch motivos de cancelación por (numero_servicio|periodo)
        $reasonsRaw = \Illuminate\Support\Facades\DB::table('payment_attempts')
            ->select('numero_servicio', 'periodo', 'reason', 'status')
            ->where('status', 'canceled')
            ->whereIn('numero_servicio', $items->pluck('numero_servicio')->filter()->unique()->all())
            ->get();
        $reasons = [];
        foreach ($reasonsRaw as $r) {
            $reasons[$r->numero_servicio.'|'.$r->periodo] = (string) $r->reason;
        }
        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="historial_recibos.csv"',
            ];
            $callback = function () use ($items, $reasons, $totalRecaudado) {
                echo "\xEF\xBB\xBF"; // BOM UTF-8
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Folio', 'Fecha', 'Monto', 'Cliente', 'Número', 'Estado', 'Motivo', 'Usuario']);
                $userNames = \App\Models\User::whereIn('id', $items->pluck('created_by')->filter()->unique())->pluck('name', 'id');
                foreach ($items as $f) {
                    $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                    $cliente = is_array($payload) ? ($payload['nombre'] ?? '') : '';
                    $motivo = $reasons[($f->numero_servicio ?? '').'|'.($f->periodo ?? '')] ?? '';
                    fputcsv($out, [
                        str_pad((string) $f->reference_number, 8, '0', STR_PAD_LEFT),
                        optional($f->created_at)->format('d/m/Y H:i'),
                        number_format((float) $f->total, 2, '.', ''),
                        $cliente,
                        $f->numero_servicio,
                        $f->deleted_at ? 'Cancelado' : 'Vigente',
                        $motivo,
                        $userNames[$f->created_by] ?? '',
                    ]);
                }
                // Fila de total (solo recaudado)
                fputcsv($out, ['', '', number_format((float) $totalRecaudado, 2, '.', ''), 'TOTAL', '', '', '', '']);
                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        } elseif (in_array($format, ['excel', 'xls', 'xlsx'], true)) {
            $headers = [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="historial_recibos.xls"',
                'Cache-Control' => 'max-age=0',
            ];
            $callback = function () use ($items, $reasons, $totalRecaudado) {
                $userNames = \App\Models\User::whereIn('id', $items->pluck('created_by')->filter()->unique())->pluck('name', 'id');
                echo "\xEF\xBB\xBF";
                echo '<html><head><meta charset="utf-8">';
                echo '<style>
                table{ border-collapse:collapse; }
                th,td{ border:1px solid #888; padding:6px 8px; font-family:Arial, Helvetica, sans-serif; font-size:11pt; }
                thead th{ background:#2e7d32; color:#fff; }
                .text{ mso-number-format:"\@"; }
                .date{ mso-number-format:"dd/mm/yyyy\\ hh:mm"; }
                .money{ mso-number-format:"\\$#,##0.00"; text-align:right; }
                .total-row td{ background:#1e3a8a; color:#fff; font-weight:700; }
                                </style></head><body>';
                echo '<table>';
                echo '<colgroup>
                <col style="width:90px"><col style="width:160px"><col style="width:110px"><col style="width:260px"><col style="width:110px"><col style="width:140px"><col style="width:150px">
                </colgroup>';
                echo '<thead><tr>
                <th>Folio</th><th>Fecha</th><th>Monto</th><th>Cliente</th><th>Número</th><th>Estado</th><th>Motivo</th><th>Usuario</th>
                </tr></thead><tbody>';
                foreach ($items as $f) {
                    $payload = is_array($f->payload) ? $f->payload : (is_string($f->payload) ? @json_decode($f->payload, true) : []);
                    $cliente = is_array($payload) ? ($payload['nombre'] ?? '') : '';
                    $folio = str_pad((string) $f->reference_number, 8, '0', STR_PAD_LEFT);
                    $fecha = optional($f->created_at)->format('d/m/Y H:i');
                    $monto = number_format((float) $f->total, 2, '.', '');
                    $numero = $f->numero_servicio;
                    $estado = $f->deleted_at ? 'Cancelado' : 'Vigente';
                    $motivo = $reasons[($f->numero_servicio ?? '').'|'.($f->periodo ?? '')] ?? '';
                    $rowStyle = $f->deleted_at ? ' style="background:#fee2e2"' : '';
                    $usuario = $userNames[$f->created_by] ?? '';
                    echo '<tr'.$rowStyle.'>';
                    echo '<td class="text">'.htmlspecialchars($folio).'</td>';
                    echo '<td class="date">'.htmlspecialchars($fecha).'</td>';
                    echo '<td class="money">'.htmlspecialchars($monto).'</td>';
                    echo '<td>'.htmlspecialchars($cliente).'</td>';
                    echo '<td class="text">'.htmlspecialchars((string) $numero).'</td>';
                    echo '<td>'.htmlspecialchars($estado).'</td>';
                    echo '<td>'.htmlspecialchars($motivo).'</td>';
                    echo '<td>'.htmlspecialchars($usuario).'</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                // Fila de total (solo recaudado)
                echo '<tfoot><tr class="total-row">';
                echo '<td class="text"></td><td class="text">TOTAL</td>';
                echo '<td class="money">'.htmlspecialchars(number_format((float) $totalRecaudado, 2, '.', '')).'</td>';
                echo '<td colspan="5"></td>';
                echo '</tr></tfoot>';
                echo '</table></body></html>';
            };

            return response()->stream($callback, 200, $headers);
        }

        return view('admin.pagos_historial_pdf', [
            'items' => $items,
            'from' => $from,
            'to' => $to,
            'cliente' => $cliente,
            'reasons' => $reasons,
            'total' => $totalRecaudado,
        ]);
    }

    public function pagosFacturaCancel(Request $request, int $id)
    {
        $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ]);
        if ($request->user()?->role !== 'admin') {
            abort(403);
        }
        $f = \App\Models\Factura::withTrashed()->findOrFail($id);
        if ($f->deleted_at) {
            return back()->with('status', 'La factura ya estaba cancelada.');
        }
        // Liberamos el fingerprint para permitir un nuevo pago tras la cancelación
        // Nos aseguramos de que el nuevo fingerprint no exceda los 64 caracteres
        if ($f->fingerprint) {
            $f->fingerprint = substr($f->fingerprint, 0, 40).'_can_'.now()->timestamp;
            $f->save();
        }
        $f->delete();
        \Illuminate\Support\Facades\DB::table('payment_attempts')->insert([
            'usuario_id' => $f->usuario_id,
            'numero_servicio' => $f->numero_servicio,
            'periodo' => $f->periodo,
            'status' => 'canceled',
            'reason' => $request->input('motivo'),
            'created_by' => optional($request->user())->id,
            'payload' => json_encode($f->payload),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Recibo cancelado correctamente.');
    }

    public function pagosLookup(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || ! ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $u = \App\Models\Usuario::with(['estado', 'estatusServicio'])->where('numero_servicio', $numero)->first();
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

    public function clientes(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $tec = strtolower(trim((string) $request->input('tec', '')));
        $query = Usuario::with(['estado', 'estatusServicio']);
        $fodMax = (int) (Usuario::max('numero_servicio') ?? 7414);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                if (ctype_digit($q)) {
                    $sub->where('numero_servicio', $q)
                        ->orWhere('telefono', 'like', "%{$q}%");
                } else {
                    $sub->where('nombre_cliente', 'like', "%{$q}%")
                        ->orWhereRaw("SOUNDEX(nombre_cliente) LIKE CONCAT(SOUNDEX(?), '%')", [$q])
                        ->orWhere('telefono', 'like', "%{$q}%");
                    $upper = strtoupper($q);
                    if (in_array($upper, ['INA', 'FOI', 'FOD'], true)) {
                        $sub->orWhere(function ($r) use ($upper) {
                            if ($upper === 'INA') {
                                $r->whereBetween('numero_servicio', [1000, 4200]);
                            } elseif ($upper === 'FOI') {
                                $r->whereBetween('numero_servicio', [4800, 5400])
                                    ->orWhereBetween('numero_servicio', [5500, 5999]);
                            } elseif ($upper === 'FOD') {
                                $r->whereBetween('numero_servicio', [5401, 5499])
                                    ->orWhere('numero_servicio', '>=', 6000);
                            }
                        });
                    }
                }
            });
        }

        if (in_array($tec, ['ina', 'foi', 'fod'], true)) {
            $query->where(function ($r) use ($tec) {
                if ($tec === 'ina') {
                    $r->whereBetween('numero_servicio', [1000, 4200]);
                } elseif ($tec === 'foi') {
                    $r->whereBetween('numero_servicio', [4800, 5400])
                        ->orWhereBetween('numero_servicio', [5500, 5999]);
                } elseif ($tec === 'fod') {
                    $r->whereBetween('numero_servicio', [5401, 5499])
                        ->orWhere('numero_servicio', '>=', 6000);
                }
            });
        }

        $clientes = $query->orderBy('numero_servicio', 'asc')->paginate(50);

        // Lógica de cambio automático a "Pendiente de pago" después del día 7
        $hoy = now();
        if ($hoy->day >= 8) {
            $periodoActual = $hoy->format('Y-m');
            foreach ($clientes as $c) {
                // Solo si está en Pagado (1) o sin estatus, y no es Cancelado (3) ni Suspendido (2)
                if (in_array($c->estatus_servicio_id, [1, null])) {
                    $pagado = \App\Models\Factura::where('numero_servicio', $c->numero_servicio)
                        ->where('periodo', $periodoActual)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $pagado) {
                        $c->update(['estatus_servicio_id' => 4]); // Pendiente de pago
                    }
                }
            }
        }

        return view('admin.clientes.index', compact('clientes', 'q', 'tec', 'fodMax'));
    }

    public function numerosDisponibles()
    {
        // Obtener el último número de cliente registrado
        $ultimoNumero = Usuario::max('numero_servicio') ?? 7418;

        // Generar rango de números desde 1000 hasta el último registro
        $rangoCompleto = range(1000, $ultimoNumero);

        // Obtener números ocupados (>= 1000)
        $numerosOcupados = Usuario::where('numero_servicio', '>=', 1000)
            ->pluck('numero_servicio')
            ->toArray();

        // Filtrar números desocupados
        $numerosDisponibles = array_diff($rangoCompleto, $numerosOcupados);

        // Ordenar y convertir a colección
        $numerosDisponibles = collect(array_values($numerosDisponibles))->sort();

        // Búsqueda específica
        $busqueda = request('busqueda');
        if ($busqueda) {
            $numerosDisponibles = $numerosDisponibles->filter(function ($numero) use ($busqueda) {
                return str_contains($numero, $busqueda);
            });
        }

        // Paginar resultados (20 por página para el modal)
        $page = request('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $numerosPaginados = $numerosDisponibles->slice($offset, $perPage);

        return response()->json([
            'numeros' => $numerosPaginados->values(),
            'total' => $numerosDisponibles->count(),
            'ultimoNumero' => $ultimoNumero,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($numerosDisponibles->count() / $perPage),
            'busqueda' => $busqueda,
        ]);
    }

    public function clientesShow(int $id)
    {
        $cliente = Usuario::with(['estado', 'estatusServicio'])->findOrFail($id);

        return view('admin.clientes.show', compact('cliente'));
    }

    public function clientesStore(Request $request)
    {
        Validator::make(
            $request->all(),
            [
                'numero_servicio' => ['required', 'numeric', 'unique:usuarios,numero_servicio'],
                'nombre_cliente' => ['required', 'string', 'max:150'],
                'domicilio' => ['nullable', 'string', 'max:255'],
                'comunidad' => ['nullable', 'string', 'max:100'],
                'telefono' => ['nullable', 'string', 'max:20'],
                'uso' => ['nullable', 'string', 'max:50'],
                'megas' => ['nullable', 'integer', 'min:0'],
                'tecnologia' => ['nullable', 'string', 'in:ina,foi,fod'],
                'dispositivo' => ['nullable', 'string', 'in:permanencia voluntaria,como dato'],
                'tarifa' => ['nullable', 'numeric', 'min:0'],
                'fecha_contratacion' => ['nullable', 'date'],
            ],
            [
                'required' => 'El campo :attribute es obligatorio.',
                'numeric' => 'El campo :attribute debe ser numérico.',
                'integer' => 'El campo :attribute debe ser un número entero.',
                'unique' => 'El :attribute ya existe.',
                'max' => 'El campo :attribute es demasiado largo.',
                'min' => 'El campo :attribute debe ser al menos :min.',
            ],
            [
                'numero_servicio' => 'número de cliente',
                'nombre_cliente' => 'nombre',
                'domicilio' => 'domicilio',
                'comunidad' => 'comunidad',
                'telefono' => 'número telefónico',
                'uso' => 'uso',
                'megas' => 'megas',
                'tarifa' => 'paquete',
                'dispositivo' => 'dispositivo',
                'fecha_contratacion' => 'fecha del siguiente cobro',
            ]
        )->validateWithBag('clienteCreate');

        $textOrDash = function ($v) {
            $s = is_null($v) ? null : trim((string) $v);

            return ($s === null || $s === '') ? '-' : $s;
        };
        $tecByNumero = function ($n) {
            $num = (int) $n;
            if ($num >= 6000 || ($num >= 5401 && $num <= 5499)) {
                return 'fod';
            }
            if (($num >= 4800 && $num <= 5400) || ($num >= 5500 && $num <= 5999)) {
                return 'foi';
            }
            if ($num >= 1000 && $num <= 4200) {
                return 'ina';
            }

            return null;
        };

        // Asignación automática de megas si hay costo y tecnología
        $megasAsignados = null;
        if ($request->filled('tarifa') && $request->filled('tecnologia')) {
            try {
                $megasAsignados = MegasAssigner::assign($request->tarifa, $request->tecnologia);
            } catch (\InvalidArgumentException $e) {
                $megasAsignados = null;
            }
        }

        Usuario::create([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $textOrDash($request->domicilio),
            'telefono' => $textOrDash($request->telefono),
            'paquete' => $request->uso ? ($request->uso.($request->tecnologia ? " {$request->tecnologia}" : '').(($megasAsignados ?? $request->megas) ? ' '.($megasAsignados ?? $request->megas).'Mbps' : '')) : null,
            'estado_id' => null,
            'estatus_servicio_id' => null,
            'servicio_id' => null,
            'comunidad' => $textOrDash($request->comunidad),
            'uso' => $textOrDash($request->uso),
            'tecnologia' => $request->filled('tecnologia') ? $textOrDash($request->tecnologia) : ($tecByNumero($request->numero_servicio) ?? '-'),
            'dispositivo' => $textOrDash($request->dispositivo),
            'megas' => $megasAsignados ?? $request->megas ?? null,
            'tarifa' => $request->tarifa ?? null,
            'fecha_contratacion' => $request->fecha_contratacion ?? null,
        ]);

        // Snapshot historial (creación)
        HistorialUsuario::create([
            'accion' => 'create',
            'captured_at' => now(),
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $textOrDash($request->domicilio),
            'telefono' => $textOrDash($request->telefono),
            'comunidad' => $textOrDash($request->comunidad),
            'uso' => $textOrDash($request->uso),
            'tecnologia' => $textOrDash($request->tecnologia),
            'dispositivo' => $textOrDash($request->dispositivo),
            'megas' => $megasAsignados ?? $request->megas,
            'tarifa' => $request->tarifa,
            'paquete' => $request->uso ? ($request->uso.($request->tecnologia ? " {$request->tecnologia}" : '').(($megasAsignados ?? $request->megas) ? ' '.($megasAsignados ?? $request->megas).'Mbps' : '')) : null,
            'estado_id' => null,
            'estatus_servicio_id' => null,
            'servicio_id' => null,
            'fecha_contratacion' => $request->fecha_contratacion,
        ]);

        return redirect()->route('admin.clientes.index')->with('status', 'cliente-creado');
    }

    public function clientesEditStore(Request $request)
    {
        Validator::make(
            $request->all(),
            [
                'id' => ['required', 'exists:usuarios,id'],
                'numero_servicio' => ['required', 'numeric', 'unique:usuarios,numero_servicio,'.$request->id],
                'nombre_cliente' => ['required', 'string', 'max:150'],
                'domicilio' => ['nullable', 'string', 'max:255'],
                'telefono' => ['nullable', 'string', 'max:20'],
                'ip' => ['nullable', 'string', 'max:50'],
                'mac' => ['nullable', 'string', 'max:50'],
                'uso' => ['nullable', 'string', 'max:50'],
                'megas' => ['nullable', 'integer', 'min:0'],
                'tecnologia' => ['nullable', 'string', 'in:ina,foi,fod'],
                'dispositivo' => ['nullable', 'string', 'in:permanencia voluntaria,como dato'],
                'tarifa' => ['nullable', 'numeric', 'min:0'],
                'estado_id' => ['nullable', 'exists:estados,id'],
                'estatus_servicio_id' => ['nullable', 'exists:estatus_servicios,id'],
            ],
            [
                'required' => 'El campo :attribute es obligatorio.',
                'numeric' => 'El campo :attribute debe ser numérico.',
                'integer' => 'El campo :attribute debe ser un número entero.',
                'unique' => 'El :attribute ya existe.',
                'numero_servicio.unique' => 'numero de servicio en uso',
                'exists' => 'El registro seleccionado no existe.',
            ],
            [
                'id' => 'registro',
                'numero_servicio' => 'número de cliente',
                'nombre_cliente' => 'nombre',
                'domicilio' => 'domicilio',
                'comunidad' => 'comunidad',
                'telefono' => 'número telefónico',
                'ip' => 'dirección IP',
                'mac' => 'MAC address',
                'uso' => 'uso',
                'megas' => 'megas',
                'tarifa' => 'paquete',
                'dispositivo' => 'dispositivo',
                'estado_id' => 'estado',
                'estatus_servicio_id' => 'estatus de servicio',
            ]
        )->validateWithBag('clienteEdit');

        $textOrDash = function ($v) {
            $s = is_null($v) ? null : trim((string) $v);

            return ($s === null || $s === '') ? '-' : $s;
        };
        $tecByNumero = function ($n) {
            $num = (int) $n;
            if ($num >= 6000 || ($num >= 5401 && $num <= 5499)) {
                return 'fod';
            }
            if (($num >= 4800 && $num <= 5400) || ($num >= 5500 && $num <= 5999)) {
                return 'foi';
            }
            if ($num >= 1000 && $num <= 4200) {
                return 'ina';
            }

            return null;
        };

        // Verificación explícita de duplicado para garantizar mensaje claro
        if (
            Usuario::where('numero_servicio', $request->numero_servicio)
                ->where('id', '!=', $request->id)
                ->exists()
        ) {
            return back()
                ->withErrors(['numero_servicio' => 'numero de servicio en uso'], 'clienteEdit')
                ->withInput();
        }

        // Asignación automática de megas si hay costo y tecnología
        $megasAsignados = null;
        if ($request->filled('tarifa') && $request->filled('tecnologia')) {
            try {
                $megasAsignados = MegasAssigner::assign($request->tarifa, $request->tecnologia);
            } catch (\InvalidArgumentException $e) {
                $megasAsignados = null;
            }
        }

        $usuario = Usuario::findOrFail($request->id);
        // Snapshot historial (antes de actualizar)
        HistorialUsuario::create([
            'usuario_original_id' => $usuario->id,
            'accion' => 'update',
            'captured_at' => now(),
            'numero_servicio' => $usuario->numero_servicio,
            'nombre_cliente' => $usuario->nombre_cliente,
            'domicilio' => $usuario->domicilio,
            'telefono' => $usuario->telefono,
            'comunidad' => $usuario->comunidad,
            'uso' => $usuario->uso,
            'tecnologia' => $usuario->tecnologia,
            'dispositivo' => $usuario->dispositivo,
            'megas' => $usuario->megas,
            'tarifa' => $usuario->tarifa,
            'paquete' => $usuario->paquete,
            'estado_id' => $usuario->estado_id,
            'estatus_servicio_id' => $usuario->estatus_servicio_id,
            'servicio_id' => $usuario->servicio_id,
            'fecha_contratacion' => $usuario->fecha_contratacion,
        ]);
        $usuario->update([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $textOrDash($request->domicilio),
            'telefono' => $textOrDash($request->telefono),
            'ip' => $textOrDash($request->ip),
            'mac' => $textOrDash($request->mac),
            'paquete' => $request->uso ? ($request->uso.($request->tecnologia ? " {$request->tecnologia}" : '').(($megasAsignados ?? $request->megas) ? ' '.($megasAsignados ?? $request->megas).'Mbps' : '')) : null,
            'comunidad' => $textOrDash($request->comunidad),
            'uso' => $textOrDash($request->uso),
            'tecnologia' => $request->filled('tecnologia') ? $textOrDash($request->tecnologia) : ($tecByNumero($request->numero_servicio) ?? '-'),
            'dispositivo' => $textOrDash($request->dispositivo),
            'megas' => $megasAsignados ?? $request->megas ?? null,
            'tarifa' => $request->tarifa ?? null,
            'estado_id' => $request->estado_id ?? null,
            'estatus_servicio_id' => $request->estatus_servicio_id ?? null,
        ]);

        return redirect()->route('admin.clientes.index')->with('status', 'cliente-actualizado');
    }

    public function create()
    {
        return response('Admin create');
    }

    public function store(Request $request)
    {
        return response('Admin store');
    }

    public function show(int $id)
    {
        return response('Admin show '.$id);
    }

    public function edit(int $id)
    {
        return response('Admin edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Admin update '.$id);
    }

    public function destroy(int $id)
    {
        return response('Admin destroy '.$id);
    }

    public function clientesDestroy(Request $request, int $id)
    {
        $usuario = Usuario::findOrFail($id);
        // Snapshot historial (antes de eliminar)
        HistorialUsuario::create([
            'usuario_original_id' => $usuario->id,
            'accion' => 'delete',
            'captured_at' => now(),
            'numero_servicio' => $usuario->numero_servicio,
            'nombre_cliente' => $usuario->nombre_cliente,
            'domicilio' => $usuario->domicilio,
            'telefono' => $usuario->telefono,
            'comunidad' => $usuario->comunidad,
            'uso' => $usuario->uso,
            'tecnologia' => $usuario->tecnologia,
            'dispositivo' => $usuario->dispositivo,
            'megas' => $usuario->megas,
            'tarifa' => $usuario->tarifa,
            'paquete' => $usuario->paquete,
            'estado_id' => $usuario->estado_id,
            'estatus_servicio_id' => $usuario->estatus_servicio_id,
            'servicio_id' => $usuario->servicio_id,
            'fecha_contratacion' => $usuario->fecha_contratacion,
        ]);
        $usuario->delete();

        if ($request->filled('redirect_to')) {
            return redirect($request->redirect_to)->with('status', 'cliente-eliminado');
        }

        return redirect()->route('admin.clientes.index')->with('status', 'cliente-eliminado');
    }

    public function clientesHistorial($numero)
    {
        $numero = (int) $numero;
        $historial = HistorialUsuario::with(['estado', 'estatusServicio'])
            ->where('numero_servicio', $numero)
            ->orderByDesc('captured_at')
            ->get();
        $actual = Usuario::where('numero_servicio', $numero)->first();

        return view('admin.clientes.historial', compact('numero', 'historial', 'actual'));
    }

    public function clientesHistorialBuscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return redirect()->route('admin.clientes.index');
        }

        if (ctype_digit($q)) {
            return redirect()->route('admin.clientes.historial', ['numero' => (int) $q]);
        }

        $activos = Usuario::where(function ($query) use ($q) {
            $query->where('nombre_cliente', 'like', '%'.$q.'%')
                ->orWhereRaw("SOUNDEX(nombre_cliente) LIKE CONCAT(SOUNDEX(?), '%')", [$q]);
        })
            ->orderBy('nombre_cliente')
            ->get(['numero_servicio', 'nombre_cliente', 'telefono']);

        $eliminadosRaw = HistorialUsuario::select('numero_servicio', 'nombre_cliente', 'telefono', 'captured_at')
            ->where('accion', 'delete')
            ->where(function ($query) use ($q) {
                $query->where('nombre_cliente', 'like', '%'.$q.'%')
                    ->orWhereRaw("SOUNDEX(nombre_cliente) LIKE CONCAT(SOUNDEX(?), '%')", [$q]);
            })
            ->orderByDesc('captured_at')
            ->get()
            ->groupBy('numero_servicio')
            ->map->first();

        $activosNumeros = $activos->pluck('numero_servicio')->filter()->all();

        $resultados = collect();
        foreach ($activos as $u) {
            $resultados->push((object) [
                'numero_servicio' => $u->numero_servicio,
                'nombre_cliente' => $u->nombre_cliente,
                'telefono' => $u->telefono,
                'estado' => 'Activo',
            ]);
        }

        if ($eliminadosRaw) {
            foreach ($eliminadosRaw as $num => $e) {
                if (! $num || in_array($num, $activosNumeros, true)) {
                    continue;
                }
                $resultados->push((object) [
                    'numero_servicio' => $e->numero_servicio,
                    'nombre_cliente' => $e->nombre_cliente,
                    'telefono' => $e->telefono,
                    'estado' => 'Eliminado',
                ]);
            }
        }

        $resultados = $resultados->sortBy('nombre_cliente')->values();

        return view('admin.clientes.historial_buscar', [
            'q' => $q,
            'resultados' => $resultados,
        ]);
    }

    public function clientesImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $file = $request->file('file');
        $path = $file->getRealPath();

        $report = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return back()->withErrors(['file' => 'No se pudo abrir el archivo.']);
            }

            $first = fgets($handle);
            if ($first === false) {
                fclose($handle);

                return back()->withErrors(['file' => 'El archivo está vacío.']);
            }
            if (str_starts_with($first, "\xEF\xBB\xBF")) {
                $first = substr($first, 3);
            }
            $fixEncoding = function ($s) {
                if ($s === null) {
                    return $s;
                }
                $s = (string) $s;
                if (! mb_check_encoding($s, 'UTF-8')) {
                    $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
                    if ($converted === false || $converted === '') {
                        $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
                    }
                    if ($converted && mb_check_encoding($converted, 'UTF-8')) {
                        return $converted;
                    }
                }

                return $s;
            };
            $first = $fixEncoding($first);
            $header = str_getcsv($first);
            if (! $header) {
                fclose($handle);

                return back()->withErrors(['file' => 'No se pudieron leer los encabezados (línea 1).']);
            }

            $normalize = function ($s) {
                $s = strtolower(trim((string) $s));
                $s = str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ', '#'], ['_', 'a', 'e', 'i', 'o', 'u', 'n', 'num'], $s);

                return $s;
            };
            $map = [];
            foreach ($header as $i => $h) {
                $map[$i] = $normalize($fixEncoding($h));
            }

            $hasPaqueteCol = in_array('paquete', $map) || in_array('plan', $map);
            $hasTarifaCol = in_array('tarifa', $map) || in_array('costo', $map);

            $lineNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                try {
                    if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                        $report['skipped']++;

                        continue;
                    }
                    $item = [];
                    foreach ($row as $i => $v) {
                        $item[$map[$i] ?? 'col_'.$i] = $fixEncoding($v);
                    }

                    $get = function ($arr, $keys) {
                        foreach ($keys as $k) {
                            if (array_key_exists($k, $arr) && $arr[$k] !== '' && $arr[$k] !== null) {
                                return $arr[$k];
                            }
                        }

                        return null;
                    };

                    $numero = $get($item, ['numero_servicio', 'numero', 'num_cliente', 'no_cliente', 'n_cliente', 'nro', 'nro_cliente', 'numero_de_servicio', 'no_de_servicio']);
                    $nombre = $get($item, ['nombre_cliente', 'nombre', 'cliente', 'nombre_del_cliente', 'nombre_de_cliente']);
                    $telefono = $get($item, ['telefono', 'tel', 'numero_telefono', 'numero_de_telefono', 'celular', 'cel']);
                    $domicilio = $get($item, ['domicilio', 'direccion', 'direccion_1', 'direccion1', 'calle']);
                    $paquete = $get($item, ['paquete', 'plan', 'paquete_plan']);
                    $tarifaRaw = $get($item, ['tarifa', 'costo', 'mensualidad', 'precio']);
                    $zonaVal = $get($item, ['zona', 'sector', 'zona_servicio']);
                    $ipVal = $get($item, ['ip', 'ip_servicio', 'direccion_ip']);
                    $macVal = $get($item, ['mac', 'mac_address', 'direccion_mac']);

                    if ($numero === null || $nombre === null) {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: falta numero_servicio o nombre_cliente";

                        continue;
                    }

                    $numero = preg_replace('/[^0-9]/', '', (string) $numero);
                    if ($numero === '') {
                        $report['skipped']++;

                        continue;
                    }

                    $tarifaValue = 300;
                    if ($tarifaRaw !== null) {
                        $norm = str_replace(['$', ' ', ','], ['', '', '.'], (string) $tarifaRaw);
                        if (is_numeric($norm)) {
                            $tarifaValue = round((float) $norm, 2);
                        }
                    }

                    $tecByNumero = function ($n) {
                        $num = (int) $n;
                        if ($num >= 6000 || ($num >= 5401 && $num <= 5499)) {
                            return 'fod';
                        }
                        if (($num >= 4800 && $num <= 5400) || ($num >= 5500 && $num <= 5999)) {
                            return 'foi';
                        }
                        if ($num >= 1000 && $num <= 4200) {
                            return 'ina';
                        }

                        return null;
                    };

                    $payload = [
                        'nombre_cliente' => trim((string) $nombre),
                        'telefono' => trim((string) $telefono) ?: '-',
                        'paquete' => trim((string) $paquete) ?: '$300',
                        'tarifa' => $tarifaValue,
                        'zona' => trim((string) $zonaVal) ?: '-',
                        'ip' => trim((string) $ipVal) ?: '-',
                        'mac' => trim((string) $macVal) ?: '-',
                        'tecnologia' => $tecByNumero($numero) ?? '-',
                    ];

                    $existing = Usuario::where('numero_servicio', $numero)->first();
                    if ($existing) {
                        $existing->update($payload);
                        $report['updated']++;
                    } else {
                        Usuario::create(array_merge(['numero_servicio' => $numero, 'domicilio' => trim((string) $domicilio) ?: '-'], $payload));
                        $report['created']++;
                    }
                } catch (\Throwable $e) {
                    $report['skipped']++;
                    $report['errors'][] = "Línea $lineNumber: ".$e->getMessage();
                }
            }
            fclose($handle);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Error: '.$e->getMessage()]);
        }

        return redirect()->route('admin.clientes.index')->with('status', "Importación: {$report['created']} creados, {$report['updated']} actualizados")->with('import_report', $report);
    }

    private function logStructuredError($message, $severity = 'error', $context = [])
    {
        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
        ];

        // Ensure logs directory exists
        $logPath = storage_path('logs/import_cartera_structured.log');
        @file_put_contents($logPath, json_encode($logEntry, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
    }

    public function clientesImportCartera(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $file = $request->file('file');
        $path = $file->getRealPath();

        $report = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'skipped_details' => [],
            'errors' => [],
        ];

        try {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                $this->logStructuredError('No se pudo abrir el archivo de importación', 'error', ['path' => $path]);

                return back()->withErrors(['file' => 'No se pudo abrir el archivo.']);
            }

            // Skip BOM if present
            $first = fgets($handle);
            if ($first === false) {
                fclose($handle);
                $this->logStructuredError('El archivo de importación está vacío', 'warning', ['path' => $path]);

                return back()->withErrors(['file' => 'El archivo está vacío.']);
            }
            if (str_starts_with($first, "\xEF\xBB\xBF")) {
                $first = substr($first, 3);
            }

            $fixEncoding = function ($s) {
                if ($s === null) {
                    return $s;
                }
                $s = (string) $s;
                if (! mb_check_encoding($s, 'UTF-8')) {
                    $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
                    if ($converted === false || $converted === '') {
                        $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
                    }
                    if ($converted && mb_check_encoding($converted, 'UTF-8')) {
                        return $converted;
                    }
                }

                return $s;
            };

            $first = $fixEncoding($first);
            $header = str_getcsv($first);

            $normalize = function ($s) {
                $s = strtolower(trim((string) $s));
                $s = str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ', '#'], ['_', 'a', 'e', 'i', 'o', 'u', 'n', 'num'], $s);

                return $s;
            };

            $map = [];
            if ($header) {
                foreach ($header as $i => $h) {
                    $map[$normalize($fixEncoding($h))] = $i;
                }
            }

            $getVal = function ($row, $keys) use ($map) {
                foreach ($keys as $k) {
                    if (isset($map[$k]) && isset($row[$map[$k]])) {
                        return trim($row[$map[$k]]);
                    }
                }

                return null;
            };

            $lineNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                try {
                    $numero = $getVal($row, ['numero_de_cliente', 'numero_servicio', 'numero', 'num_cliente']);
                    $nombre = $getVal($row, ['nombre', 'nombre_cliente', 'cliente']);
                    $telefono = $getVal($row, ['numero_telefonico', 'telefono', 'tel', 'celular']);
                    $megas = $getVal($row, ['megas', 'mbps', 'velocidad']);
                    $tarifa = $getVal($row, ['tarifa', 'costo', 'paquete', 'mensualidad']);
                    $estado = $getVal($row, ['estado', 'estatus']);
                    $descripcionAdeudo = $getVal($row, ['descripcion', 'observaciones', 'nota']);
                    $montoAdeudo = $getVal($row, ['cantidad', 'monto_adeudo', 'adeudo']);

                    if (! $numero) {
                        $numero = trim($row[0] ?? '');
                    }
                    if (! $nombre) {
                        $nombre = trim($row[1] ?? '');
                    }
                    if (! $telefono) {
                        $telefono = trim($row[2] ?? '');
                    }
                    if (! $megas) {
                        $megas = trim($row[3] ?? '');
                    }
                    if (! $tarifa) {
                        $tarifa = trim($row[4] ?? '');
                    }
                    if (! $descripcionAdeudo) {
                        $descripcionAdeudo = trim($row[5] ?? '');
                    }
                    if (! $montoAdeudo) {
                        $montoAdeudo = trim($row[6] ?? '');
                    }
                    if (! $estado) {
                        $estado = trim($row[10] ?? '');
                    }

                    if (empty($numero) || ! is_numeric($numero)) {
                        $report['skipped']++;
                        $report['skipped_details'][] = "Línea $lineNumber: Número de cliente inválido o vacío ('$numero')";

                        continue;
                    }

                    $nombre = trim((string) $nombre);
                    if ($nombre === '') {
                        $report['skipped']++;
                        $report['skipped_details'][] = "Línea $lineNumber: Nombre de cliente vacío para el número '$numero'";

                        continue;
                    }

                    $usuario = Usuario::where('numero_servicio', $numero)->first();
                    $updateData = [];
                    $updateData['nombre_cliente'] = $nombre;
                    if ($telefono) {
                        // Limitar teléfono a 20 caracteres para evitar SQL error
                        $updateData['telefono'] = substr($telefono, 0, 20);
                    }
                    if ($megas && is_numeric($megas)) {
                        $updateData['megas'] = (int) $megas;
                    }
                    if ($tarifa) {
                        $t = str_replace(['$', ' ', ','], ['', '', ''], $tarifa);
                        if (is_numeric($t)) {
                            $updateData['tarifa'] = (float) $t;
                        }
                    }

                    if ($descripcionAdeudo !== null) {
                        $updateData['adeudo_descripcion'] = $descripcionAdeudo;
                    }
                    if ($montoAdeudo !== null) {
                        $ma = str_replace(['$', ' ', ','], ['', '', ''], $montoAdeudo);
                        if (is_numeric($ma)) {
                            $updateData['adeudo_monto'] = (float) $ma;
                        }
                    }

                    if ($estado) {
                        $est = strtoupper($estado);
                        if ($est === 'SI' || $est === 'PAGADO' || $est === 'ACTIVADO') {
                            $updateData['estatus_servicio_id'] = 1;
                            $updateData['estado_id'] = 1;
                        } elseif ($est === 'NO' || $est === 'PENDIENTE' || $est === 'SUSPENDIDO') {
                            $updateData['estatus_servicio_id'] = 4;
                        }
                    }

                    if ($usuario) {
                        $usuario->update($updateData);
                        $report['updated']++;
                    } else {
                        $updateData['numero_servicio'] = $numero;
                        if (! isset($updateData['domicilio'])) {
                            $updateData['domicilio'] = '-';
                        }
                        Usuario::create($updateData);
                        $report['created']++;
                    }
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    // Limpiar mensajes SQL comunes
                    if (str_contains($msg, 'Data too long for column \'telefono\'')) {
                        $msg = 'El número telefónico es demasiado largo (máx 20 caracteres).';
                    } elseif (str_contains($msg, 'Data too long for column \'nombre_cliente\'')) {
                        $msg = 'El nombre del cliente es demasiado largo.';
                    } elseif (str_contains($msg, 'doesn\'t have a default value')) {
                        $col = explode("'", $msg)[1] ?? 'desconocida';
                        $msg = "Falta el campo obligatorio: $col";
                    }

                    $errorInfo = [
                        'type' => get_class($e),
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                        'name' => $e->getMessage(),
                        'stack' => $e->getTraceAsString(),
                        'variables' => [
                            'lineNumber' => $lineNumber,
                            'numero_servicio' => $numero ?? 'N/A',
                            'nombre_cliente' => $nombre ?? 'N/A',
                            'row_data' => $row,
                        ],
                    ];
                    $this->logStructuredError("Error procesando línea $lineNumber (Cliente $numero)", 'error', $errorInfo);
                    $report['errors'][] = "Línea $lineNumber (Cliente $numero): ".$msg;
                }
            }
            fclose($handle);
        } catch (\Throwable $e) {
            $errorInfo = [
                'type' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'name' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ];
            $this->logStructuredError('Error general en importación de cartera', 'error', $errorInfo);
            $report['errors'][] = 'Error general: '.$e->getMessage();
        }

        return back()->with('import_report', $report);
    }
}
