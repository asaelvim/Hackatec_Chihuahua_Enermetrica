<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnomalyResource;
use App\Models\Anomaly;
use Illuminate\Http\Request;

class AnomalyController extends Controller
{
    /**
     * Display a listing of the resource, optionally filtered by device
     * or by review status.
     */
    public function index(Request $request)
    {
        $anomalies = Anomaly::query()
            ->with(['device', 'consumptionReading'])
            ->when($request->integer('device_id'), fn ($query, $deviceId) => $query->where('device_id', $deviceId))
            ->when($request->boolean('only_unreviewed'), fn ($query) => $query->whereNull('reviewed_at'))
            ->latest('created_at')
            ->paginate();

        return AnomalyResource::collection($anomalies);
    }

    /**
     * Display the specified resource.
     */
    public function show(Anomaly $anomaly)
    {
        return new AnomalyResource($anomaly->load(['device', 'consumptionReading']));
    }

    /**
     * Mark the anomaly as reviewed by an administrator.
     */
    public function review(Anomaly $anomaly)
    {
        $anomaly->update(['reviewed_at' => now()]);

        return new AnomalyResource($anomaly->load(['device', 'consumptionReading']));
    }
}
