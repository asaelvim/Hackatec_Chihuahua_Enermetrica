<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsumptionReadingRequest;
use App\Http\Resources\ConsumptionReadingResource;
use App\Models\ConsumptionReading;
use App\Models\Device;
use App\Services\ConsumptionIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConsumptionReadingController extends Controller
{
    public function __construct(protected ConsumptionIngestionService $ingestionService) {}

    /**
     * Display a listing of readings, optionally filtered by device and date range.
     */
    public function index(Request $request)
    {
        $readings = ConsumptionReading::query()
            ->when($request->integer('device_id'), fn ($query, $deviceId) => $query->where('device_id', $deviceId))
            ->when($request->date('from'), fn ($query, $from) => $query->where('read_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->where('read_at', '<=', $to))
            ->latest('read_at')
            ->paginate();

        return ConsumptionReadingResource::collection($readings);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConsumptionReading $consumptionReading)
    {
        return new ConsumptionReadingResource($consumptionReading);
    }

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
