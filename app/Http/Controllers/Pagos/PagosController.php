<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PagosController extends Controller
{
    public function index()
    {
        return view('pagos.index');
    }

    public function create()
    {
        return response('Pagos create');
    }

    public function store(Request $request)
    {
        return response('Pagos store');
    }

    public function show(int $id)
    {
        return response('Pagos show '.$id);
    }

    public function edit(int $id)
    {
        return response('Pagos edit '.$id);
    }

    public function update(Request $request, int $id)
    {
        return response('Pagos update '.$id);
    }

    public function destroy(int $id)
    {
        return response('Pagos destroy '.$id);
    }
}
