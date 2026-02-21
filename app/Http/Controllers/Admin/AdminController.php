<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function clientes()
    {
        $clientes = Usuario::latest()->get();
        return view('admin.clientes.index', compact('clientes'));
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
                'tarifa' => ['nullable', 'numeric', 'min:0'],
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
                'tarifa' => 'tarifa',
            ]
        )->validateWithBag('clienteCreate');

        Usuario::create([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $request->domicilio,
            'telefono' => $request->telefono,
            'paquete' => $request->uso ? ($request->uso . ($request->megas ? " {$request->megas}Mbps" : '')) : null,
            'estado_id' => null,
            'estatus_servicio_id' => null,
            'servicio_id' => null,
            'comunidad' => $request->comunidad ?? null,
            'uso' => $request->uso ?? null,
            'megas' => $request->megas ?? null,
            'tarifa' => $request->tarifa ?? null,
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
                'tarifa' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'required' => 'El campo :attribute es obligatorio.',
                'numeric' => 'El campo :attribute debe ser numérico.',
                'integer' => 'El campo :attribute debe ser un número entero.',
                'unique' => 'El :attribute ya existe.',
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
                'tarifa' => 'tarifa',
            ]
        )->validateWithBag('clienteEdit');

        $usuario = Usuario::findOrFail($request->id);
        $usuario->update([
            'numero_servicio' => $request->numero_servicio,
            'nombre_cliente' => $request->nombre_cliente,
            'domicilio' => $request->domicilio,
            'telefono' => $request->telefono,
            'paquete' => $request->uso ? ($request->uso . ($request->megas ? " {$request->megas}Mbps" : '')) : null,
            'comunidad' => $request->comunidad ?? null,
            'uso' => $request->uso ?? null,
            'megas' => $request->megas ?? null,
            'tarifa' => $request->tarifa ?? null,
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
}
