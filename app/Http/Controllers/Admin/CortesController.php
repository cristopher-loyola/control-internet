<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Cortador;
use App\Models\Factura;
use App\Services\MorosidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CortesController extends Controller
{
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
            $mesesAdeudo = $adeudo['meses_adeudo'] ?? 0;
            $desdePeriodo = $adeudo['desde_periodo'] ?? $mesActual;

            // Lógica de corte:
            // - 0 meses adeudo → Verde (al día)
            // - Solo debe el mes actual (meses_adeudo == 1 && desde_periodo == mesActual) → Verde siempre
            // - Debe meses anteriores (meses_adeudo >= 2 O desde_periodo < mesActual) + día < 8 → Verde (tolerancia)
            // - Debe meses anteriores (meses_adeudo >= 2 O desde_periodo < mesActual) + día >= 8 → Blanco (corte)
            if ($mesesAdeudo == 0) {
                $usuario->pagado_mes = true;
            } elseif ($mesesAdeudo == 1 && $desdePeriodo === $mesActual) {
                // Solo debe el mes actual → siempre verde
                $usuario->pagado_mes = true;
            } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes < 8) {
                // Debe 1+ meses anteriores pero aún es antes del día 8 → verde (tolerancia)
                $usuario->pagado_mes = true;
            } elseif ($mesesAdeudo >= 1 && $desdePeriodo < $mesActual && $diaDelMes >= 8) {
                // Debe 1+ meses anteriores y ya es día 8 o después → blanco (corte)
                $usuario->pagado_mes = false;
            } else {
                // Caso por defecto: cualquier otro caso de adeudo → blanco (corte)
                $usuario->pagado_mes = false;
            }

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
                $vence = $f->created_at->copy()->addMonths($months);
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
}
