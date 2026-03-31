<?php

namespace App\Http\Controllers\PozoHondo;

use App\Http\Controllers\Controller;
use App\Services\ZonaDashboardService;
use Illuminate\Http\Request;

class PozoHondoController extends Controller
{
    public function index(Request $request)
    {
        $zona = 'Pozo Hondo';

        return view('pozo_hondo.index', [
            'zona' => $zona,
            'stats' => ZonaDashboardService::stats($zona),
            'chart' => ZonaDashboardService::chartNewClientsLast7Days($zona),
            'payments' => ZonaDashboardService::recentPayments($zona, 10),
        ]);
    }

    public function pagos(Request $request)
    {
        return view('pozo_hondo.pagos');
    }
}
