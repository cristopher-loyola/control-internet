<?php

namespace App\Http\Controllers\Rosalito;

use App\Http\Controllers\Controller;
use App\Services\ZonaDashboardService;
use Illuminate\Http\Request;

class RosalitoController extends Controller
{
    public function index(Request $request)
    {
        $zona = 'Rosalito';

        return view('rosalito.index', [
            'zona' => $zona,
            'stats' => ZonaDashboardService::stats($zona),
            'chart' => ZonaDashboardService::chartNewClientsLast7Days($zona),
            'payments' => ZonaDashboardService::recentPayments($zona, 10),
        ]);
    }
}
