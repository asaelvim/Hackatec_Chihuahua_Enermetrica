<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyConsumptionSummaryResource;
use App\Models\DailyConsumptionSummary;
use Illuminate\Http\Request;

class DailyConsumptionSummaryController extends Controller
{
    /**
     * Display a listing of the resource, optionally filtered by device
     * and date range.
     */
    public function index(Request $request)
    {
        $summaries = DailyConsumptionSummary::query()
            ->with('device')
            ->when($request->integer('device_id'), fn ($query, $deviceId) => $query->where('device_id', $deviceId))
            ->when($request->date('from'), fn ($query, $from) => $query->where('date', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->where('date', '<=', $to))
            ->orderByDesc('date')
            ->paginate();

        return DailyConsumptionSummaryResource::collection($summaries);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyConsumptionSummary $dailyConsumptionSummary)
    {
        return new DailyConsumptionSummaryResource($dailyConsumptionSummary->load('device'));
    }
}
