<?php

namespace App\Console\Commands;

use App\Models\DailyConsumptionSummary;
use App\Models\Device;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $devicesSummarized = 0;

        Device::query()->whereHas('consumptionReadings', function ($query) use ($date) {
            $query->whereBetween('read_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        })->chunkById(50, function ($devices) use ($date, &$devicesSummarized) {
            foreach ($devices as $device) {
                $stats = $device->consumptionReadings()
                    ->whereBetween('read_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                    ->select([
                        DB::raw('SUM(value) as total_value'),
                        DB::raw('AVG(value) as avg_value'),
                        DB::raw('MIN(value) as min_value'),
                        DB::raw('MAX(value) as max_value'),
                        DB::raw('COUNT(*) as readings_count'),
                    ])
                    ->first();

                DailyConsumptionSummary::updateOrCreate(
                    ['device_id' => $device->id, 'date' => $date->toDateString()],
                    [
                        'total_kwh' => $stats->total_value / 1000,
                        'avg_watts' => $stats->avg_value,
                        'min_watts' => $stats->min_value,
                        'max_watts' => $stats->max_value,
                        'readings_count' => $stats->readings_count,
                    ]
                );

                $devicesSummarized++;
            }
        });

        $this->info("Resumen diario calculado para {$devicesSummarized} dispositivo(s) del {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
