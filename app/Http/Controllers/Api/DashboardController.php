<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummaryService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Resumen para la pantalla principal de la app móvil: contadores de
     * dispositivos, gráfica de consumo (24h o la última hora, según
     * ?range=), top 5 dispositivos, anomalías recientes y costo estimado
     * del mes.
     */
    public function index(Request $request, DashboardSummaryService $dashboard)
    {
        $range = $request->string('range')->toString() === '1h' ? '1h' : '24h';

        return response()->json($dashboard->summary($range));
    }
}
