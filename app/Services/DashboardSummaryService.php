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
    public function summary(string $chartRange = '24h'): array
    {
        return [
            'totalDevices' => Device::count(),
            'onDevices' => Device::where('status', 'on')->count(),
            'offlineDevices' => Device::where('status', 'offline')->count(),
            'maintenanceDevices' => Device::where('status', 'maintenance')->count(),
            'pendingAnomalies' => Anomaly::whereNull('reviewed_at')->count(),
            'chartData' => $this->consumptionChartData($chartRange),
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

    /**
     * Datos de la gráfica de consumo total. Soporta tres rangos:
     * - '24h' (por defecto): 24 cubetas de una hora cada una.
     * - '1h': 60 cubetas de un minuto cada una, para notar variaciones
     *   recientes (p.ej. el simulador en vivo) que una vista de 24h diluye.
     * - '5m': 60 cubetas de 5 segundos cada una (la misma cadencia con la
     *   que el simulador genera lecturas), para ver subidas/bajadas casi
     *   en tiempo real.
     */
    public function consumptionChartData(string $range = '24h'): array
    {
        return match ($range) {
            '5m' => $this->consumptionChartDataForLast5Minutes(),
            '1h' => $this->consumptionChartDataForLastHour(),
            default => $this->consumptionChartDataForLast24Hours(),
        };
    }

    private function consumptionChartDataForLast24Hours(): array
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

        return $this->buildChartPayload($labels, $data, 'Consumo total (kW) — últimas 24h');
    }

    private function consumptionChartDataForLastHour(): array
    {
        $start = Carbon::now()->subMinutes(59)->startOfMinute();

        $readings = ConsumptionReading::query()
            ->selectRaw('DATE_FORMAT(read_at, "%Y-%m-%d %H:%i:00") as bucket, AVG(value) as average')
            ->where('read_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('average', 'bucket');

        $labels = [];
        $data = [];

        for ($i = 0; $i < 60; $i++) {
            $minute = $start->copy()->addMinutes($i);
            $labels[] = $minute->format('H:i');
            $data[] = round((float) ($readings[$minute->format('Y-m-d H:i:00')] ?? 0) / 1000, 3);
        }

        return $this->buildChartPayload($labels, $data, 'Consumo promedio (kW) — última hora');
    }

    private function consumptionChartDataForLast5Minutes(): array
    {
        $bucketSeconds = 5;
        $buckets = 60; // 60 cubetas de 5s = 5 minutos

        $start = Carbon::now()->subSeconds($bucketSeconds * ($buckets - 1));
        $start->subSeconds($start->second % $bucketSeconds)->startOfSecond();

        // Igual que las demás vistas, agrupamos comparando el valor literal
        // guardado en read_at (sin pasar por UNIX_TIMESTAMP, que depende de
        // la zona horaria de SESIÓN de MySQL y puede no coincidir con la de
        // la app en hosting compartido, dejando esta gráfica siempre en 0
        // aunque las lecturas sí existan).
        $readings = ConsumptionReading::query()
            ->selectRaw('DATE_FORMAT(DATE_SUB(read_at, INTERVAL (SECOND(read_at) MOD ?) SECOND), "%Y-%m-%d %H:%i:%s") as bucket, AVG(value) as average', [$bucketSeconds])
            ->where('read_at', '>=', $start)
            ->groupBy('bucket')
            ->pluck('average', 'bucket');

        $labels = [];
        $data = [];

        for ($i = 0; $i < $buckets; $i++) {
            $moment = $start->copy()->addSeconds($i * $bucketSeconds);
            $labels[] = $moment->format('H:i:s');
            $data[] = round((float) ($readings[$moment->format('Y-m-d H:i:s')] ?? 0) / 1000, 3);
        }

        return $this->buildChartPayload($labels, $data, 'Consumo promedio (kW) — últimos 5 minutos');
    }

    private function buildChartPayload(array $labels, array $data, string $label): array
    {
        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => $label,
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
