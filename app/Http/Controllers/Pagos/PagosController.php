<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use App\Models\AppSetting;

class PagosController extends Controller
{
    public function index()
    {
        return view('pagos.index');
    }

    public function recibos()
    {
        return view('pagos.recibos');
    }

    public function recibosLayoutGet()
    {
        $setting = AppSetting::find('receipt_layout');
        return response()->json([
            'ok' => true,
            'layout' => $setting ? $setting->value : null
        ]);
    }

    public function recibosLookup(Request $request)
    {
        $numero = (string) $request->query('numero');
        if ($numero === '' || !ctype_digit($numero)) {
            return response()->json(['ok' => false, 'message' => 'Número inválido'], 422);
        }
        $u = Usuario::with(['estado', 'estatusServicio'])->where('numero_servicio', $numero)->first();
        if (!$u) {
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

    public function recibosFacturaStore(Request $request)
    {
        $request->validate([
            'numero_servicio' => ['nullable', 'string'],
            'usuario_id' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric'],
            'payload' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($request) {
            $row = DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->lockForUpdate()
                ->first();
            if (!$row) {
                DB::table('invoice_sequences')->insert([
                    'name' => 'facturas',
                    'current_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = (object)['current_value' => 0];
            }
            $payload = $request->input('payload', []);
            $fingerprintData = [
                'numero_servicio' => $request->input('numero_servicio'),
                'total' => round((float)$request->input('total', 0), 2),
                'nombre' => $payload['nombre'] ?? null,
                'mensualidad' => $payload['mensualidad'] ?? null,
                'recargo' => $payload['recargo'] ?? null,
                'pago_anterior' => $payload['pago_anterior'] ?? null,
                'metodo' => $payload['metodo'] ?? null,
            ];
            $fingerprint = hash('sha256', json_encode($fingerprintData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

            $existing = Factura::withTrashed()
                ->where(function($q) use ($fingerprint, $request){
                    $q->where('fingerprint', $fingerprint);
                })
                ->orWhere(function($q) use ($request){
                    $q->where('numero_servicio', $request->input('numero_servicio'))
                        ->where('total', $request->input('total', 0))
                        ->whereRaw('payload = ?', [json_encode($request->input('payload', []))]);
                })
                ->orderBy('id', 'desc')
                ->first();
            if ($existing) {
                return response()->json([
                    'ok' => true,
                    'referencia' => $existing->reference_number,
                    'id' => $existing->id,
                    'reused' => true,
                ]);
            }
            $next = (int) $row->current_value + 1;
            DB::table('invoice_sequences')
                ->where('name', 'facturas')
                ->update(['current_value' => $next, 'updated_at' => now()]);

            try {
                $factura = new Factura();
                $factura->reference_number = $next;
                $factura->usuario_id = $request->input('usuario_id');
                $factura->numero_servicio = $request->input('numero_servicio');
                $factura->total = $request->input('total', 0);
                $factura->payload = $payload;
                $factura->created_by = $request->user()?->id;
                $factura->fingerprint = $fingerprint;
                $factura->save();
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Si falla por fingerprint duplicado (carrera), buscamos el que ganó
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

            return response()->json([
                'ok' => true,
                'referencia' => $factura->reference_number,
                'id' => $factura->id,
            ]);
        });
    }

    public function recibosFacturasIndex(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $rows = Factura::orderByDesc('id')->limit($limit)->get([
            'id', 'reference_number', 'numero_servicio', 'total', 'created_at'
        ]);
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function recibosFacturaShow(int $id)
    {
        $f = Factura::findOrFail($id);
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

    public function recibosFacturaByFolio(int $ref)
    {
        $f = Factura::where('reference_number', $ref)->first();
        if (!$f) {
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

    public function create()
    {
        return response('Pagos create');
    }

    public function store(Request $request)
    {
        return response('Pagos store');
    }

    public function show(int $id)
    {
        return response('Pagos show '.$id);
    }

    public function edit(int $id)
    {
        return response('Pagos edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Pagos update '.$id);
    }

    public function destroy(int $id)
    {
        return response('Pagos destroy '.$id);
    }
}
