<?php

namespace App\Services;

use App\Models\DailyConsumptionSummary;
use Illuminate\Support\Carbon;

/**
 * Centraliza el resumen de estadísticas (totales, gráfica diaria y
 * costo estimado para un rango de fechas/dispositivo), compartido entre
 * el panel web (Livewire) y la API de la app móvil.
 */
class StatisticsSummaryService
{
    public function totals(?int $deviceId, string $from, string $to): object
    {
        return DailyConsumptionSummary::query()
            ->when($deviceId, fn ($query) => $query->where('device_id', $deviceId))
            ->when($from, fn ($query) => $query->where('date', '>=', $from))
            ->when($to, fn ($query) => $query->where('date', '<=', $to))
            ->selectRaw('SUM(total_kwh) as total_kwh, AVG(avg_watts) as avg_watts, MAX(max_watts) as max_watts')
            ->first();
    }

    public function estimatedCost(?int $deviceId, string $from, string $to): array
    {
        $totals = $this->totals($deviceId, $from, $to);

        return (new CfeTariffCalculator)->estimate(
            (float) ($totals->total_kwh ?? 0),
            Carbon::parse($from ?: Carbon::now()->subDays(6)),
            Carbon::parse($to ?: Carbon::now()),
        );
    }

    public function chartData(?int $deviceId, string $from, string $to): array
    {
        $rows = DailyConsumptionSummary::query()
            ->when($deviceId, fn ($query) => $query->where('device_id', $deviceId))
            ->when($from, fn ($query) => $query->where('date', '>=', $from))
            ->when($to, fn ($query) => $query->where('date', '<=', $to))
            ->selectRaw('date, SUM(total_kwh) as total_kwh')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => Carbon::parse($row->date)->format('d/m'))->all(),
            'datasets' => [[
                'label' => 'Consumo diario (kWh)',
                'data' => $rows->map(fn ($row) => (float) $row->total_kwh)->all(),
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                'fill' => true,
                'tension' => 0.3,
                'pointRadius' => 2,
            ]],
        ];
    }
}
