<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cobrador;
use Illuminate\Http\Request;

class CobradoresController extends Controller
{
    public function index()
    {
        return response()->json(Cobrador::orderBy('orden')->orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:60|unique:cobradores,nombre',
        ]);

        $maxOrden = Cobrador::max('orden') ?? 0;
        $cobrador = Cobrador::create([
            'nombre' => ucfirst(trim($data['nombre'])),
            'orden'  => $maxOrden + 1,
            'activo' => true,
        ]);

        return response()->json($cobrador, 201);
    }

    public function destroy(Cobrador $cobrador)
    {
        $cobrador->delete();
        return response()->json(['ok' => true]);
    }

    public function toggle(Cobrador $cobrador)
    {
        $cobrador->update(['activo' => !$cobrador->activo]);
        return response()->json($cobrador->fresh());
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        foreach ($request->ids as $orden => $id) {
            Cobrador::where('id', $id)->update(['orden' => $orden + 1]);
        }
        return response()->json(['ok' => true]);
    }
}
