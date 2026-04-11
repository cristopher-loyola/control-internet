<?php

namespace App\Http\Controllers\Tecnico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\HistorialUsuario;
use App\Models\Cortador;
use App\Services\MorosidadService;
use Dompdf\Dompdf;
use Dompdf\Options;

class TecnicoController extends Controller
{
    public function index()
    {
        return view('tecnico.index');
    }

    public function create()
    {
        return response('Técnico create');
    }

    public function store(Request $request)
    {
        return response('Técnico store');
    }

    public function show(int $id)
    {
        return response('Técnico show '.$id);
    }

    public function edit(int $id)
    {
        return response('Técnico edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Técnico update '.$id);
    }

    public function destroy(int $id)
    {
        return response('Técnico destroy '.$id);
    }

    public function clientes(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $tec = trim((string) $request->query('tec', ''));
        
        $clientes = Usuario::with(['estado', 'estatusServicio'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre_cliente', 'like', "%{$q}%")
                        ->orWhere('numero_servicio', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('domicilio', 'like', "%{$q}%");
                });
            })
            ->when($tec !== '', function ($query) use ($tec) {
                $query->where(function ($sub) use ($tec) {
                    $sub->where('tecnologia', $tec);
                });
            })
            ->orderBy('numero_servicio', 'asc')
            ->paginate(50);
        return view('tecnico.clientes.index', compact('clientes'));
    }

    public function clientesShow(int $id)
    {
        $cliente = Usuario::with(['estado', 'estatusServicio'])->findOrFail($id);
        return view('tecnico.clientes.show', compact('cliente'));
    }

    public function clientesHistorial($numero)
    {
        $numero = (int) $numero;
        $historial = HistorialUsuario::with(['estado', 'estatusServicio'])
            ->where('numero_servicio', $numero)
            ->orderBy('created_at', 'desc')
            ->get();
        $actual = Usuario::with(['estado', 'estatusServicio'])
            ->where('numero_servicio', $numero)
            ->first();
        return view('tecnico.clientes.historial', compact('numero', 'historial', 'actual'));
    }

    public function clientesHistorialBuscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return redirect()->route('tecnico.clientes.index');
        }
        $cliente = Usuario::with(['estado', 'estatusServicio'])
            ->where('numero_servicio', $q)
            ->orWhere('nombre_cliente', 'like', "%{$q}%")
            ->first();
        if (!$cliente) {
            return redirect()->route('tecnico.clientes.index')->with('error', 'Cliente no encontrado');
        }
        return redirect()->route('tecnico.clientes.historial', $cliente->numero_servicio);
    }

    public function clientesEdit(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:usuarios,id'],
            'numero_servicio' => ['required', 'string'],
            'nombre_cliente' => ['required', 'string'],
            'domicilio' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string'],
            'uso' => ['nullable', 'string'],
            'tecnologia' => ['nullable', 'string'],
            'dispositivo' => ['nullable', 'string'],
            'megas' => ['nullable', 'integer'],
            'tarifa' => ['nullable', 'numeric'],
            'estado_id' => ['nullable', 'integer'],
            'estatus_servicio_id' => ['nullable', 'integer'],
        ], [], [], 'clienteEdit');

        $cliente = Usuario::findOrFail($request->input('id'));

        // Guardar historial antes de actualizar
        HistorialUsuario::create([
            'numero_servicio' => $cliente->numero_servicio,
            'nombre_cliente' => $cliente->nombre_cliente,
            'domicilio' => $cliente->domicilio,
            'telefono' => $cliente->telefono,
            'estado_id' => $cliente->estado_id,
            'estatus_servicio_id' => $cliente->estatus_servicio_id,
            'usuario_original_id' => $cliente->id,
            'accion' => 'Actualizado',
        ]);

        // Actualizar cliente
        $cliente->numero_servicio = $request->input('numero_servicio');
        $cliente->nombre_cliente = $request->input('nombre_cliente');
        $cliente->domicilio = $request->input('domicilio');
        $cliente->telefono = $request->input('telefono');
        $cliente->uso = $request->input('uso');
        $cliente->tecnologia = $request->input('tecnologia');
        $cliente->dispositivo = $request->input('dispositivo');
        $cliente->megas = $request->input('megas');
        $cliente->tarifa = $request->input('tarifa');
        $cliente->estado_id = $request->input('estado_id');
        $cliente->estatus_servicio_id = $request->input('estatus_servicio_id');
        $cliente->save();

        return redirect()->route('tecnico.clientes.index')->with('status', 'cliente-actualizado');
    }

    /**
     * Exportar a PDF los usuarios que NO están en verde (solo por cortar)
     */
    public function exportCortesPdf(Request $request, MorosidadService $morosidadService)
    {
        $q = trim((string) $request->query('q', ''));
        $zona = $request->query('zona');
        $estado = $request->query('estado');

        $mesActual = now()->format('Y-m');
        $diaDelMes = now()->day;

        $usuarios = Usuario::with('cortador')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('numero_servicio', 'like', "%{$q}%")
                        ->orWhere('nombre_cliente', 'like', "%{$q}%")
                        ->orWhere('zona', 'like', "%{$q}%");
                });
            })
            ->when($zona, function ($query) use ($zona) {
                $query->where('zona', $zona);
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado_corte', $estado);
            })
            ->orderBy('numero_servicio', 'asc')
            ->get();

        // Calcular adeudo y filtrar solo los NO verdes (por cortar)
        $usuariosPorCortar = $usuarios->filter(function ($usuario) use ($morosidadService, $diaDelMes, $mesActual) {
            $adeudo = $morosidadService->calcularAdeudoUsuario((string)$usuario->numero_servicio);
            $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
            $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;

            // Determinar si está en verde (al día)
            $pagadoMes = false;
            if ($mesesAdeudo == 0) {
                $pagadoMes = true;
            } elseif ($mesesAdeudo == 1 && $desdePeriodo === $mesActual) {
                $pagadoMes = true;
            } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes < 8) {
                $pagadoMes = true;
            }

            // Solo incluir los que NO están en verde
            return !$pagadoMes;
        });

        $cortadores = Cortador::orderBy('nombre')->get();
        $titulo = 'USUARIOS POR CORTAR - ' . now()->locale('es')->monthName . ' ' . now()->year;

        $html = view('admin.cortes_pdf', compact('usuariosPorCortar', 'cortadores', 'titulo'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->stream('usuarios-por-cortar-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Exportar a CSV (Excel) los usuarios que NO están en verde (solo por cortar)
     */
    public function exportCortesCsv(Request $request, MorosidadService $morosidadService)
    {
        $q = trim((string) $request->query('q', ''));
        $zona = $request->query('zona');
        $estado = $request->query('estado');

        $mesActual = now()->format('Y-m');
        $diaDelMes = now()->day;

        $usuarios = Usuario::with('cortador')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('numero_servicio', 'like', "%{$q}%")
                        ->orWhere('nombre_cliente', 'like', "%{$q}%")
                        ->orWhere('zona', 'like', "%{$q}%");
                });
            })
            ->when($zona, function ($query) use ($zona) {
                $query->where('zona', $zona);
            })
            ->when($estado, function ($query) use ($estado) {
                $query->where('estado_corte', $estado);
            })
            ->orderBy('numero_servicio', 'asc')
            ->get();

        // Calcular adeudo y filtrar solo los NO verdes (por cortar)
        $usuariosPorCortar = $usuarios->filter(function ($usuario) use ($morosidadService, $diaDelMes, $mesActual) {
            $adeudo = $morosidadService->calcularAdeudoUsuario((string)$usuario->numero_servicio);
            $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
            $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;

            // Determinar si está en verde (al día)
            $pagadoMes = false;
            if ($mesesAdeudo == 0) {
                $pagadoMes = true;
            } elseif ($mesesAdeudo == 1 && $desdePeriodo === $mesActual) {
                $pagadoMes = true;
            } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes < 8) {
                $pagadoMes = true;
            }

            // Solo incluir los que NO están en verde
            return !$pagadoMes;
        });

        $filename = 'usuarios-por-cortar-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($usuariosPorCortar) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8

            // Encabezados
            fputcsv($file, ['ID', 'Nombre Cliente', 'Zona', 'IP', 'MAC', 'Cortador Asignado', 'Estado Corte']);

            // Datos
            foreach ($usuariosPorCortar as $u) {
                fputcsv($file, [
                    $u->numero_servicio,
                    $u->nombre_cliente,
                    $u->zona ?? '-',
                    $u->ip ?? '-',
                    $u->mac ?? '-',
                    $u->cortador?->nombre ?? '-',
                    $u->estado_corte ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
