<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatisticsController extends Controller
{
    /**
     * Totales, gráfica diaria y costo estimado para un rango de fechas
     * (y opcionalmente un dispositivo), usado por la pantalla de
     * Estadísticas de la app móvil.
     */
    public function summary(Request $request, StatisticsSummaryService $service)
    {
        $deviceId = $request->integer('device_id') ?: null;
        $from = $request->string('from')->toString() ?: Carbon::now()->subDays(6)->toDateString();
        $to = $request->string('to')->toString() ?: Carbon::now()->toDateString();

        return response()->json([
            'totals' => $service->totals($deviceId, $from, $to),
            'chartData' => $service->chartData($deviceId, $from, $to),
            'estimatedCost' => $service->estimatedCost($deviceId, $from, $to),
        ]);
    }
}
