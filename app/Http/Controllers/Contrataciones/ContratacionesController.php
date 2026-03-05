<?php

namespace App\Http\Controllers\Contrataciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
use App\Services\MegasAssigner;
use App\Models\HistorialUsuario;

class ContratacionesController extends Controller
{
    public function index()
    {
        return view('contrataciones.index');
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

        $clientes = $query->orderBy('numero_servicio', 'asc')->get();
        return view('contrataciones.clientes.index', compact('clientes', 'q', 'tec', 'fodMax'));
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

        $megasAsignados = null;
        if ($request->filled('tarifa') && $request->filled('tecnologia')) {
            try {
                $megasAsignados = MegasAssigner::assign($request->tarifa, $request->tecnologia);
            } catch (\InvalidArgumentException $e) {
                $megasAsignados = null;
            }
        }

        $textOrDash = function ($v) {
            $s = is_null($v) ? null : trim((string) $v);
            return ($s === null || $s === '') ? '-' : $s;
        };
        $tecByNumero = function ($n) {
            $num = (int) $n;
            if ($num >= 6000 || ($num >= 5401 && $num <= 5499)) return 'fod';
            if (($num >= 4800 && $num <= 5400) || ($num >= 5500 && $num <= 5999)) return 'foi';
            if ($num >= 1000 && $num <= 4200) return 'ina';
            return null;
        };

        Usuario::create([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $textOrDash($request->domicilio),
            'telefono' => $textOrDash($request->telefono),
            'paquete' => $request->uso ? ($request->uso . ($request->tecnologia ? " {$request->tecnologia}" : '') . (($megasAsignados ?? $request->megas) ? " " . ($megasAsignados ?? $request->megas) . "Mbps" : '')) : null,
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

        HistorialUsuario::create([
            'accion' => 'create',
            'captured_at' => now(),
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $request->domicilio,
            'telefono' => $request->telefono,
            'comunidad' => $request->comunidad,
            'uso' => $request->uso,
            'tecnologia' => $request->tecnologia,
            'dispositivo' => $request->dispositivo,
            'megas' => $megasAsignados ?? $request->megas,
            'tarifa' => $request->tarifa,
            'paquete' => $request->uso ? ($request->uso . ($request->tecnologia ? " {$request->tecnologia}" : '') . (($megasAsignados ?? $request->megas) ? " " . ($megasAsignados ?? $request->megas) . "Mbps" : '')) : null,
            'estado_id' => null,
            'estatus_servicio_id' => null,
            'servicio_id' => null,
            'fecha_contratacion' => $request->fecha_contratacion,
        ]);

        return redirect()->route('contrataciones.clientes.index')->with('status', 'cliente-creado');
    }

    public function create()
    {
        return response('Contrataciones create');
    }

    public function store(Request $request)
    {
        return response('Contrataciones store');
    }

    public function show(int $id)
    {
        return response('Contrataciones show '.$id);
    }

    public function clientesShow(int $id)
    {
        $cliente = Usuario::with(['estado', 'estatusServicio'])->findOrFail($id);
        return view('contrataciones.clientes.show', compact('cliente'));
    }

    public function edit(int $id)
    {
        return response('Contrataciones edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Contrataciones update '.$id);
    }

    public function clientesEditStore(Request $request)
    {
        Validator::make(
            $request->all(),
            [
                'id' => ['required', 'exists:usuarios,id'],
                'numero_servicio' => ['required', 'numeric', 'unique:usuarios,numero_servicio,' . $request->id],
                'nombre_cliente' => ['required', 'string', 'max:150'],
                'domicilio' => ['nullable', 'string', 'max:255'],
                'comunidad' => ['nullable', 'string', 'max:100'],
                'telefono' => ['nullable', 'string', 'max:20'],
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
                'uso' => 'uso',
                'megas' => 'megas',
                'tarifa' => 'paquete',
                'dispositivo' => 'dispositivo',
                'estado_id' => 'estado',
                'estatus_servicio_id' => 'estatus de servicio',
            ]
        )->validateWithBag('clienteEdit');

        if (
            Usuario::where('numero_servicio', $request->numero_servicio)
                ->where('id', '!=', $request->id)
                ->exists()
        ) {
            return back()
                ->withErrors(['numero_servicio' => 'numero de servicio en uso'], 'clienteEdit')
                ->withInput();
        }

        $megasAsignados = null;
        if ($request->filled('tarifa') && $request->filled('tecnologia')) {
            try {
                $megasAsignados = MegasAssigner::assign($request->tarifa, $request->tecnologia);
            } catch (\InvalidArgumentException $e) {
                $megasAsignados = null;
            }
        }

        $usuario = Usuario::findOrFail($request->id);
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
        $textOrDash = function ($v) {
            $s = is_null($v) ? null : trim((string) $v);
            return ($s === null || $s === '') ? '-' : $s;
        };
        $tecByNumero = function ($n) {
            $num = (int) $n;
            if ($num >= 6000 || ($num >= 5401 && $num <= 5499)) return 'fod';
            if (($num >= 4800 && $num <= 5400) || ($num >= 5500 && $num <= 5999)) return 'foi';
            if ($num >= 1000 && $num <= 4200) return 'ina';
            return null;
        };

        $usuario->update([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $textOrDash($request->domicilio),
            'telefono' => $textOrDash($request->telefono),
            'paquete' => $request->uso ? ($request->uso . ($request->tecnologia ? " {$request->tecnologia}" : '') . (($megasAsignados ?? $request->megas) ? " " . ($megasAsignados ?? $request->megas) . "Mbps" : '')) : null,
            'comunidad' => $textOrDash($request->comunidad),
            'uso' => $textOrDash($request->uso),
            'tecnologia' => $request->filled('tecnologia') ? $textOrDash($request->tecnologia) : ($tecByNumero($request->numero_servicio) ?? '-'),
            'dispositivo' => $textOrDash($request->dispositivo),
            'megas' => $megasAsignados ?? $request->megas ?? null,
            'tarifa' => $request->tarifa ?? null,
            'estado_id' => $request->estado_id ?? null,
            'estatus_servicio_id' => $request->estatus_servicio_id ?? null,
        ]);

        return redirect()->route('contrataciones.clientes.index')->with('status', 'cliente-actualizado');
    }
    public function destroy(int $id)
    {
        return response('Contrataciones destroy '.$id);
    }

    public function clientesHistorial($numero)
    {
        $numero = (int) $numero;
        $historial = HistorialUsuario::with(['estado', 'estatusServicio'])
            ->where('numero_servicio', $numero)
            ->orderByDesc('captured_at')
            ->get();
        $actual = Usuario::where('numero_servicio', $numero)->first();
        return view('contrataciones.clientes.historial', compact('numero', 'historial', 'actual'));
    }

    public function clientesHistorialBuscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return redirect()->route('contrataciones.clientes.index');
        }

        if (ctype_digit($q)) {
            return redirect()->route('contrataciones.clientes.historial', ['numero' => (int) $q]);
        }

        $activos = Usuario::where(function ($query) use ($q) {
                $query->where('nombre_cliente', 'like', '%' . $q . '%')
                    ->orWhereRaw("SOUNDEX(nombre_cliente) LIKE CONCAT(SOUNDEX(?), '%')", [$q]);
            })
            ->orderBy('nombre_cliente')
            ->get(['numero_servicio', 'nombre_cliente', 'telefono']);

        $eliminadosRaw = HistorialUsuario::select('numero_servicio', 'nombre_cliente', 'telefono', 'captured_at')
            ->where('accion', 'delete')
            ->where(function ($query) use ($q) {
                $query->where('nombre_cliente', 'like', '%' . $q . '%')
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

        foreach ($eliminadosRaw as $num => $e) {
            if (!$num || in_array($num, $activosNumeros, true)) {
                continue;
            }
            $resultados->push((object) [
                'numero_servicio' => $e->numero_servicio,
                'nombre_cliente' => $e->nombre_cliente,
                'telefono' => $e->telefono,
                'estado' => 'Eliminado',
            ]);
        }

        $resultados = $resultados->sortBy('nombre_cliente')->values();

        return view('contrataciones.clientes.historial_buscar', [
            'q' => $q,
            'resultados' => $resultados,
        ]);
    }
}
