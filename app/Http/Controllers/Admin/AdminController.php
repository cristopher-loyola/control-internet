<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use App\Services\MegasAssigner;
use App\Models\HistorialUsuario;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function pagos()
    {
        return view('admin.pagos');
    }

    public function clientes(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $tec = strtolower(trim((string) $request->input('tec', '')));
        $query = Usuario::with(['estado', 'estatusServicio']);

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
                                  ->orWhereBetween('numero_servicio', [6000, 7414]);
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
                      ->orWhereBetween('numero_servicio', [6000, 7414]);
                }
            });
        }

        $clientes = $query->orderBy('numero_servicio', 'asc')->get();
        return view('admin.clientes.index', compact('clientes', 'q', 'tec'));
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
            'domicilio' => $request->domicilio,
            'telefono' => $request->telefono,
            'paquete' => $request->uso ? ($request->uso . ($request->tecnologia ? " {$request->tecnologia}" : '') . (($megasAsignados ?? $request->megas) ? " " . ($megasAsignados ?? $request->megas) . "Mbps" : '')) : null,
            'estado_id' => null,
            'estatus_servicio_id' => null,
            'servicio_id' => null,
            'comunidad' => $request->comunidad ?? null,
            'uso' => $request->uso ?? null,
            'tecnologia' => $request->tecnologia ?? null,
            'dispositivo' => $request->dispositivo ?? null,
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

        return redirect()->route('admin.clientes.index')->with('status', 'cliente-creado');
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
            'domicilio' => $request->domicilio,
            'telefono' => $request->telefono,
            'paquete' => $request->uso ? ($request->uso . ($request->tecnologia ? " {$request->tecnologia}" : '') . (($megasAsignados ?? $request->megas) ? " " . ($megasAsignados ?? $request->megas) . "Mbps" : '')) : null,
            'comunidad' => $request->comunidad ?? null,
            'uso' => $request->uso ?? null,
            'tecnologia' => $request->tecnologia ?? null,
            'dispositivo' => $request->dispositivo ?? null,
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

    public function clientesDestroy(int $id)
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
            ->map->first(); // tomar el más reciente por número

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

        // ordenar por nombre para consistencia
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
                if ($s === null) return $s;
                $s = (string) $s;
                if (!mb_check_encoding($s, 'UTF-8')) {
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
            if (!$header) {
                fclose($handle);
                return back()->withErrors(['file' => 'No se pudieron leer los encabezados (línea 1).']);
            }

            $normalize = function ($s) {
                $s = strtolower(trim((string) $s));
                $s = str_replace([' ', 'á','é','í','ó','ú','ñ','#'], ['_','a','e','i','o','u','n','num'], $s);
                return $s;
            };
            $map = [];
            foreach ($header as $i => $h) {
                $map[$i] = $normalize($fixEncoding($h));
            }

            $lineNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                try {
                    if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: fila vacía";
                        continue;
                    }
                    $item = [];
                    foreach ($row as $i => $v) {
                        $item[$map[$i] ?? 'col_'.$i] = $fixEncoding($v);
                    }
                    $numeroKeys = ['numero_servicio','numero','num_cliente','no_cliente','n_cliente','nro','nro_cliente','numero_de_servicio','no_de_servicio'];
                    $nombreKeys = ['nombre_cliente','nombre','cliente','nombre_del_cliente','nombre_de_cliente'];
                    $telKeys = ['telefono','tel','numero_telefono','numero_de_telefono','celular','cel'];
                    $get = function ($arr, $keys) {
                        foreach ($keys as $k) {
                            if (array_key_exists($k, $arr) && $arr[$k] !== '' && $arr[$k] !== null) {
                                return $arr[$k];
                            }
                        }
                        return null;
                    };
                    $numero = $get($item, $numeroKeys);
                    $nombre = $get($item, $nombreKeys);
                    $telefono = $get($item, $telKeys);

                    if ($numero === null || $nombre === null) {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: falta numero_servicio o nombre_cliente";
                        continue;
                    }

                    $numero = preg_replace('/[^0-9]/', '', (string) $numero);
                    if ($numero === '') {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: numero_servicio inválido";
                        continue;
                    }

                    $nombreVal = trim((string) $nombre);
                    if ($nombreVal === '') {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: el nombre del cliente está vacío";
                        continue;
                    }
                    if (mb_strlen($nombreVal, 'UTF-8') > 150) {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: el nombre del cliente supera 150 caracteres";
                        continue;
                    }

                    $allowNullPhone = $request->boolean('telefono_nullable', true);
                    $telValue = null;
                    if ($telefono !== null) {
                        $t = preg_replace('/\s+/', '', trim((string) $telefono));
                        if ($t !== '') {
                            $telValue = $t;
                        } elseif (!$allowNullPhone) {
                            $telValue = '';
                        }
                    }
                    if ($telValue !== null && strlen($telValue) > 20) {
                        $report['skipped']++;
                        $report['errors'][] = "Línea $lineNumber: teléfono demasiado largo (máximo 20 caracteres)";
                        continue;
                    }
                    $payload = [
                        'nombre_cliente' => $nombreVal,
                        'telefono' => $telValue,
                    ];

                    $existing = Usuario::where('numero_servicio', $numero)->first();
                    if ($existing) {
                        $existing->update($payload);
                        $report['updated']++;
                    } else {
                        Usuario::create(array_merge([
                            'numero_servicio' => $numero,
                        ], $payload));
                        $report['created']++;
                    }
                } catch (\Throwable $e) {
                    $report['skipped']++;
                    $msg = $e->getMessage();
                    $msgLower = strtolower($msg);
                    if (str_contains($msgLower, 'data too long for column') && str_contains($msgLower, 'telefono')) {
                        $msg = 'teléfono demasiado largo (máximo 20 caracteres)';
                    } elseif (str_contains($msgLower, 'incorrect string value')) {
                        $msg = 'caracteres no válidos (codificación) en algún campo';
                    } elseif (str_contains($msgLower, 'duplicate entry') && str_contains($msgLower, 'numero_servicio')) {
                        $msg = 'número de servicio duplicado';
                    }
                    $report['errors'][] = "Línea $lineNumber: $msg";
                }
            }
            fclose($handle);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Error procesando el archivo: '.$e->getMessage()]);
        }

        $summary = "Importación: {$report['created']} creados, {$report['updated']} actualizados, {$report['skipped']} omitidos";
        return redirect()
            ->route('admin.clientes.index')
            ->with('status', $summary)
            ->with('import_report', $report);
    }
}
