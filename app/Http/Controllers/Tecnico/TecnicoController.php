<?php

namespace App\Http\Controllers\Tecnico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
