<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\ConsumptionReading;
use Illuminate\Support\Carbon;

class AnomalyDetectionService
{
    /**
     * Minimum historical readings required before a z-score is considered
     * statistically meaningful.
     */
    protected const MIN_READINGS = 5;

    /**
     * Evaluate whether a reading is anomalous compared to the device's
     * recent history, and persist an Anomaly record when it is.
     *
     * The new reading itself is excluded from the baseline mean/stddev so
     * it cannot inflate its own threshold.
     */
    public function evaluate(ConsumptionReading $reading, float $threshold = 2.0, int $lookbackDays = 30): ?Anomaly
    {
        $stats = ConsumptionReading::query()
            ->where('device_id', $reading->device_id)
            ->where('id', '!=', $reading->id)
            ->where('read_at', '>=', Carbon::now()->subDays($lookbackDays))
            ->selectRaw('AVG(value) as mean_value, STDDEV(value) as stddev_value, COUNT(*) as readings_count')
            ->first();

        if (! $stats || (int) $stats->readings_count < self::MIN_READINGS || (float) $stats->stddev_value <= 0.0) {
            return null;
        }

        $zScore = ((float) $reading->value - (float) $stats->mean_value) / (float) $stats->stddev_value;

        if ($zScore < $threshold) {
            return null;
        }

        return Anomaly::create([
            'device_id' => $reading->device_id,
            'consumption_reading_id' => $reading->id,
            'z_score' => $zScore,
            'value' => $reading->value,
        ]);
    }
}
