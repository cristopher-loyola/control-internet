<?php

namespace App\Http\Controllers\Contrataciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContratacionesController extends Controller
{
    public function index()
    {
        return view('contrataciones.index');
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

    public function edit(int $id)
    {
        return response('Contrataciones edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Contrataciones update '.$id);
    }

    public function destroy(int $id)
    {
        return response('Contrataciones destroy '.$id);
    }
}
