<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Cortador;
use App\Models\Factura;
use App\Services\MorosidadService;
use App\Services\PrepayDashboardService;
use App\Services\CortesExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class CortesController extends Controller
{
    private function debeSerCortado($usuario, $adeudo, $mesActual, $diaDelMes, MorosidadService $morosidadService): bool
    {
        return $morosidadService->debeSerCortado($usuario, $adeudo, $mesActual, $diaDelMes);
    }

    /**
     * Igual que "Cliente con adeudos: Adeuda desde {mes}" en los tickets de pago:
     * usa desde_mes_label (prioriza adeudo_descripcion real) en vez de contar
     * meses_adeudo, que solo cuenta meses extra desde el corte de importación y
     * subestima la deuda real en clientes con adeudo manual importado de Excel.
     */
    private function pagoLabel(array $adeudo): string
    {
        $pendiente = (float) ($adeudo['pendiente'] ?? 0);
        if ($pendiente <= 0.01) {
            return 'Al corriente';
        }
        $desdeLabel = trim((string) ($adeudo['desde_mes_label'] ?? ''));
        $desdeTexto = $desdeLabel === ''
            ? 'Adeudo'
            : (stripos($desdeLabel, 'adeuda') === false ? "Adeuda desde {$desdeLabel}" : $desdeLabel);

        return $desdeTexto . ' · $' . number_format($pendiente, 2);
    }

    public function index(Request $request, MorosidadService $morosidadService)
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
            ->paginate(50)
            ->appends($request->query());

        // Calcular adeudo para cada usuario y determinar si está en verde
        $usuarios->getCollection()->transform(function ($usuario) use ($morosidadService, $diaDelMes, $mesActual) {
            $adeudo = $morosidadService->calcularAdeudoUsuario((string)$usuario->numero_servicio);
            $usuario->pagado_mes = !$this->debeSerCortado($usuario, $adeudo, $mesActual, $diaDelMes, $morosidadService);

            $usuario->pago_al_corriente = ((float) ($adeudo['pendiente'] ?? 0)) <= 0.01;
            $usuario->pago_label = $this->pagoLabel($adeudo);

            return $usuario;
        });

        $cortadores = Cortador::orderBy('nombre')->get();
        $zonas = Usuario::whereNotNull('zona')->distinct()->pluck('zona');

        $prefix = $request->segment(1);
        $view = 'admin.cortes';
        if ($prefix === 'pagos') $view = 'pagos.cortes';
        if ($prefix === 'tecnico') $view = 'tecnico.cortes';

        return view($view, compact('usuarios', 'cortadores', 'zonas', 'mesActual'));
    }

    public function updateCorte(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        
        $request->validate([
            'cortador_id' => 'nullable|exists:cortadores,id',
            'estado_corte' => 'nullable|string|in:Cortado,Offline,Ya cortado,NO_ESTABA,Reactivado',
        ]);

        $updateData = [];

        // Solo actualizar los campos que vienen en la petición
        if ($request->has('cortador_id')) {
            $updateData['cortador_id'] = $request->cortador_id;
        }

        if ($request->has('estado_corte')) {
            $updateData['estado_corte'] = $request->estado_corte;
            
            // Si el estado de corte está siendo establecido a un valor de corte real
            if (in_array($request->estado_corte, ['Cortado', 'Offline', 'Ya cortado'])) {
                $updateData['fecha_corte'] = now();
                $updateData['estatus_servicio_id'] = 2; // Suspendido
                $updateData['estado_id'] = 2;           // Desactivado
            }

            // Si se selecciona Reactivado, actualizar estatus a Pagado/Activado y limpiar estado_corte
            if ($request->estado_corte === 'Reactivado') {
                $updateData['estatus_servicio_id'] = 1; // Pagado
                $updateData['estado_id'] = 1;           // Activado
                $updateData['estado_corte'] = null;     // Limpiar para que desaparezca de la lista
            }
        }

        if (!empty($updateData)) {
            $usuario->update($updateData);
        }

        return response()->json(['ok' => true]);
    }

    public function reactivacionesIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $mesActual = now()->format('Y-m');
        $hoy = now();

        // 1. Usuarios con pago registrado en el mes actual
        $usuariosPagadosMes = Factura::where('periodo', $mesActual)
            ->pluck('numero_servicio')
            ->toArray();

        // 2. Usuarios con pago adelantado
        $prepagosActivos = Factura::where('created_at', '>=', now()->subYear())
            ->where('payload->prepay', 'si')
            ->get();

        $usuariosConPrepago = [];
        foreach ($prepagosActivos as $f) {
            $p = $f->payload;
            $months = intval($p['prepay_months'] ?? 0);
            if ($months > 0) {
                $vence = ($p['prepay_next_month'] ?? false) === true
                    ? PrepayDashboardService::venceAt($f->created_at, $months, true)
                    : $f->created_at->copy()->addMonths($months);
                if ($vence->greaterThanOrEqualTo($hoy)) {
                    $usuariosConPrepago[] = (string) $f->numero_servicio;
                }
            }
        }

        $todosPagados = array_unique(array_merge(
            array_map('strval', $usuariosPagadosMes), 
            $usuariosConPrepago
        ));

        // Filtrar usuarios que tienen estado_corte (fueron cortados) Y que ya pagaron
        // Además, nos aseguramos de que tengan un cortador asignado (campos completos)
        $usuarios = Usuario::with('cortador')
            ->whereIn('numero_servicio', $todosPagados)
            ->whereIn('estado_corte', ['Cortado', 'Offline', 'Ya cortado'])
            ->whereNotNull('cortador_id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sq) use ($q) {
                    $sq->where('numero_servicio', 'like', "%{$q}%")
                        ->orWhere('nombre_cliente', 'like', "%{$q}%")
                        ->orWhere('zona', 'like', "%{$q}%");
                });
            })
            ->orderBy('numero_servicio', 'asc')
            ->paginate(50)
            ->appends($request->query());

        $cortadores = Cortador::orderBy('nombre')->get();

        $prefix = $request->segment(1);
        $view = 'admin.reactivaciones';
        if ($prefix === 'pagos') $view = 'pagos.reactivaciones';
        if ($prefix === 'tecnico') $view = 'tecnico.reactivaciones';

        return view($view, compact('usuarios', 'cortadores', 'mesActual'));
    }

    // CRUD Cortadores
    public function storeCortador(Request $request)
    {
        $request->validate(['nombre' => 'required|string|unique:cortadores,nombre']);
        $cortador = Cortador::create(['nombre' => $request->nombre]);
        return response()->json(['ok' => true, 'cortador' => $cortador]);
    }

    public function destroyCortador($id)
    {
        Cortador::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Exportar a PDF los usuarios que NO están en verde (solo por cortar)
     */
    public function exportPdf(Request $request, MorosidadService $morosidadService)
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
            return $this->debeSerCortado($usuario, $adeudo, $mesActual, $diaDelMes, $morosidadService);
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
    public function exportCsv(Request $request, MorosidadService $morosidadService)
    {
        $q = trim((string) $request->query('q', ''));
        $zona = $request->query('zona');
        $estado = $request->query('estado');

        $mesActual = now()->format('Y-m');
        $diaDelMes = now()->day;
        $mesAnterior = now()->subMonth()->format('Y-m');

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
        $usuariosPorCortar = $usuarios->filter(function ($usuario) use ($morosidadService, $diaDelMes, $mesActual, $mesAnterior) {
            $adeudo = $morosidadService->calcularAdeudoUsuario((string)$usuario->numero_servicio);
            $usuario->resaltar_adeudo_corte = ($adeudo['desde_periodo'] ?? $mesActual) < $mesAnterior;
            return $this->debeSerCortado($usuario, $adeudo, $mesActual, $diaDelMes, $morosidadService);
        });

        return app(CortesExcelExporter::class)->download($usuariosPorCortar);
    }
}
