<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummaryService;

class DashboardController extends Controller
{
    /**
     * Resumen para la pantalla principal de la app móvil: contadores de
     * dispositivos, gráfica de consumo de las últimas 24h, top 5
     * dispositivos, anomalías recientes y costo estimado del mes.
     */
    public function index(DashboardSummaryService $dashboard)
    {
        return response()->json($dashboard->summary());
    }
}
