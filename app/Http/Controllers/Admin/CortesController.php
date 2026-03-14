<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Cortador;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CortesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $zona = $request->query('zona');
        $estado = $request->query('estado');

        $mesActual = now()->format('Y-m');
        $hoy = now();

        // 1. Usuarios con pago registrado en el mes actual
        $usuariosPagadosMes = Factura::where('periodo', $mesActual)
            ->pluck('numero_servicio')
            ->toArray();

        // 2. Usuarios con pago adelantado que aún cubre este mes
        // Buscamos facturas de los últimos 12 meses que tengan prepago
        $prepagosActivos = Factura::where('created_at', '>=', now()->subYear())
            ->where('payload->prepay', 'si')
            ->get();

        $usuariosConPrepago = [];
        foreach ($prepagosActivos as $f) {
            $p = $f->payload;
            $months = intval($p['prepay_months'] ?? 0);
            if ($months > 0) {
                // Si la fecha de creación + meses de prepago es >= hoy
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

        // Marcar usuarios pagados
        $usuarios->getCollection()->transform(function ($usuario) use ($todosPagados) {
            $usuario->pagado_mes = in_array((string)$usuario->numero_servicio, $todosPagados);
            return $usuario;
        });

        $cortadores = Cortador::orderBy('nombre')->get();
        $zonas = Usuario::whereNotNull('zona')->distinct()->pluck('zona');

        return view('admin.cortes', compact('usuarios', 'cortadores', 'zonas', 'mesActual'));
    }

    public function updateCorte(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        
        $request->validate([
            'cortador_id' => 'nullable|exists:cortadores,id',
            'estado_corte' => 'nullable|string|in:Cortado,Offline,Ya cortado,NO_ESTABA',
        ]);

        $usuario->update([
            'cortador_id' => $request->cortador_id,
            'estado_corte' => $request->estado_corte,
            'fecha_corte' => now(),
        ]);

        return response()->json(['ok' => true]);
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
