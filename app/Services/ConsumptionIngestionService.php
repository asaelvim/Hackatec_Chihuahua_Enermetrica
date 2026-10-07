<?php

namespace App\Services;

use App\Models\Anomaly;
use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Models\User;
use App\Notifications\AnomalyDetected;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class ConsumptionIngestionService
{
    public function __construct(
        protected ScheduleService $scheduleService,
        protected AnomalyDetectionService $anomalyDetectionService,
    ) {}

    /**
     * Store an incoming sensor reading, refresh the device's status
     * according to its schedules, and evaluate it for anomalies.
     *
     * @return array{reading: ConsumptionReading, anomaly: ?Anomaly}
     */
    public function ingest(Device $device, float $value, ?Carbon $readAt = null): array
    {
        $readAt ??= Carbon::now();

        $reading = ConsumptionReading::create([
            'device_id' => $device->id,
            'value' => $value,
            'read_at' => $readAt,
        ]);

        $device->update([
            'status' => $this->scheduleService->determineStatus($device),
            'last_reading_at' => $readAt,
        ]);

        $anomaly = $this->anomalyDetectionService->evaluate($reading);

        if ($anomaly) {
            Notification::send(User::all(), new AnomalyDetected($anomaly));
        }

        return ['reading' => $reading, 'anomaly' => $anomaly];
    }
}
