<?php

namespace App\Http\Controllers\Chivato;

use App\Http\Controllers\Controller;
use App\Services\ZonaDashboardService;
use Illuminate\Http\Request;

class ChivatoController extends Controller
{
    public function index(Request $request)
    {
        $zona = 'Chivato';

        return view('chivato.index', [
            'zona' => $zona,
            'stats' => ZonaDashboardService::stats($zona),
            'chart' => ZonaDashboardService::chartNewClientsLast7Days($zona),
            'payments' => ZonaDashboardService::recentPayments($zona, 10),
        ]);
    }

    public function pagos(Request $request)
    {
        return view('chivato.pagos');
    }
}
