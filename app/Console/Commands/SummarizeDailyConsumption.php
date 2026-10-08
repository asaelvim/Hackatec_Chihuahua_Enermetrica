<?php

namespace App\Console\Commands;

use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:summarize-daily-consumption {--date= : Fecha a resumir (Y-m-d), por defecto ayer}')]
#[Description('Calcula/actualiza el resumen diario de consumo (daily_consumption_summaries) por dispositivo')]
class SummarizeDailyConsumption extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::yesterday();

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();
        // Ventana extra para capturar la lectura inmediatamente posterior a medianoche
        // y así poder cerrar el último intervalo de energía del día sin cortarlo de golpe.
        $windowEnd = $dayEnd->copy()->addHours(3);

        $devicesSummarized = 0;

        Device::query()->whereHas('consumptionReadings', function ($query) use ($dayStart, $dayEnd) {
            $query->whereBetween('read_at', [$dayStart, $dayEnd]);
        })->chunkById(50, function ($devices) use ($dayStart, $dayEnd, $windowEnd, $date, &$devicesSummarized) {
            foreach ($devices as $device) {
                $readings = $device->consumptionReadings()
                    ->whereBetween('read_at', [$dayStart, $windowEnd])
                    ->orderBy('read_at')
                    ->get(['value', 'read_at'])
                    ->values();

                $dayReadingsCount = $readings->filter(fn ($reading) => $reading->read_at->lte($dayEnd))->count();

                if ($dayReadingsCount === 0) {
                    continue;
                }

                // Integra la energía (Wh) usando la duración real entre cada lectura y la
                // siguiente, en vez de sumar los watts como si cada lectura representara
                // una hora completa (eso duplicaba la energía con lecturas cada 30 min).
                $totalWh = 0.0;
                $sumValue = 0.0;
                $minValue = null;
                $maxValue = null;

                for ($i = 0; $i < $dayReadingsCount; $i++) {
                    $reading = $readings[$i];
                    $next = $readings->get($i + 1);
                    $intervalEndTimestamp = $next
                        ? min($next->read_at->getTimestamp(), $dayEnd->getTimestamp())
                        : $dayEnd->getTimestamp();

                    $hours = max(0, ($intervalEndTimestamp - $reading->read_at->getTimestamp()) / 3600);
                    $totalWh += $reading->value * $hours;

                    $sumValue += $reading->value;
                    $minValue = $minValue === null ? $reading->value : min($minValue, $reading->value);
                    $maxValue = $maxValue === null ? $reading->value : max($maxValue, $reading->value);
                }

                DailyConsumptionSummary::updateOrCreate(
                    ['device_id' => $device->id, 'date' => $date->toDateString()],
                    [
                        'total_kwh' => $totalWh / 1000,
                        'avg_watts' => $sumValue / $dayReadingsCount,
                        'min_watts' => $minValue,
                        'max_watts' => $maxValue,
                        'readings_count' => $dayReadingsCount,
                    ]
                );

                $devicesSummarized++;
            }
        });

        $this->info("Resumen diario calculado para {$devicesSummarized} dispositivo(s) del {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
