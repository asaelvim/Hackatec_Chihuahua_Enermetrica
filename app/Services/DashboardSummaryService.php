<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\Area;
use App\Models\ConsumptionReading;
use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Support\Carbon;

/**
 * Centraliza los datos del dashboard (contadores, gráfica de las últimas
 * 24h, top dispositivos, anomalías recientes y costo estimado del mes),
 * para que tanto el panel web (Livewire) como la API de la app móvil
 * muestren exactamente lo mismo.
 */
class DashboardSummaryService
{
    public function summary(): array
    {
        return [
            'totalDevices' => Device::count(),
            'onDevices' => Device::where('status', 'on')->count(),
            'offlineDevices' => Device::where('status', 'offline')->count(),
            'maintenanceDevices' => Device::where('status', 'maintenance')->count(),
            'pendingAnomalies' => Anomaly::whereNull('reviewed_at')->count(),
            'chartData' => $this->consumptionChartData(),
            'topDevices' => $this->topDevices(),
            'recentAnomalies' => Anomaly::with('device')->latest('id')->limit(5)->get(),
            'areaBreakdown' => Area::withCount('devices')->orderByDesc('devices_count')->get(),
            'monthlyCost' => $this->monthlyCostEstimate(),
        ];
    }

    /**
     * Costo estimado (aproximado, tarifa CFE residencial) del mes en curso:
     * lo acumulado hasta hoy y una proyección a fin de mes basada en el
     * promedio diario de consumo observado.
     *
     * @return array{accumulated: float, projected: float, daysElapsed: int, daysInMonth: int, projectedKwh: float, projectedCapacity: float}
     */
    public function monthlyCostEstimate(): array
    {
        $today = Carbon::now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $kwhSoFar = (float) DailyConsumptionSummary::query()
            ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
            ->sum('total_kwh');

        $calculator = new CfeTariffCalculator;

        $accumulated = $calculator->estimate($kwhSoFar, $monthStart, $today)['total'];

        $daysElapsed = $today->day;
        $daysInMonth = $monthStart->daysInMonth;
        $projectedKwh = $daysElapsed > 0 ? ($kwhSoFar / $daysElapsed) * $daysInMonth : 0.0;

        $projectedEstimate = $calculator->estimate($projectedKwh, $monthStart, $monthEnd);

        return [
            'accumulated' => $accumulated,
            'projected' => $projectedEstimate['total'],
            'daysElapsed' => $daysElapsed,
            'daysInMonth' => $daysInMonth,
            'projectedKwh' => $projectedKwh,
            'projectedCapacity' => $projectedEstimate['capacity'],
        ];
    }

    public function consumptionChartData(): array
    {
        $start = Carbon::now()->subHours(23)->startOfHour();

        $readings = ConsumptionReading::query()
            ->selectRaw('DATE_FORMAT(read_at, "%Y-%m-%d %H:00:00") as bucket, SUM(value) as total')
            ->where('read_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 24; $i++) {
            $hour = $start->copy()->addHours($i);
            $labels[] = $hour->format('H:i');
            $data[] = round((float) ($readings[$hour->format('Y-m-d H:00:00')] ?? 0) / 1000, 3);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Consumo total (kW)',
                'data' => $data,
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 0,
            ]],
        ];
    }

    public function topDevices()
    {
        $start = Carbon::now()->subHours(24);

        return ConsumptionReading::query()
            ->with('device.area')
            ->selectRaw('device_id, SUM(value) as total, AVG(value) as average')
            ->where('read_at', '>=', $start)
            ->groupBy('device_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }
}
