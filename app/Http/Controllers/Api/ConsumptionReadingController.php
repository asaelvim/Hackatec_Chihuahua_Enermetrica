<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsumptionReadingRequest;
use App\Http\Resources\ConsumptionReadingResource;
use App\Models\Device;
use App\Services\ConsumptionIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ConsumptionReadingController extends Controller
{
    public function __construct(protected ConsumptionIngestionService $ingestionService) {}

    /**
     * Store a new consumption reading reported by a device/sensor.
     */
    public function store(StoreConsumptionReadingRequest $request, Device $device): JsonResponse
    {
        $readAt = $request->filled('read_at') ? Carbon::parse($request->input('read_at')) : null;

        $result = $this->ingestionService->ingest($device, (float) $request->input('value'), $readAt);

        return (new ConsumptionReadingResource($result['reading']))
            ->additional([
                'device_status' => $device->fresh()->status,
                'anomaly_detected' => $result['anomaly'] !== null,
            ])
            ->response()
            ->setStatusCode(201);
    }
}
