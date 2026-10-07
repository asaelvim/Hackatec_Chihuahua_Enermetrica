<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyConsumptionSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device' => new DeviceResource($this->whenLoaded('device')),
            'date' => $this->date->toDateString(),
            'total_kwh' => $this->total_kwh,
            'avg_watts' => $this->avg_watts,
            'min_watts' => $this->min_watts,
            'max_watts' => $this->max_watts,
            'readings_count' => $this->readings_count,
        ];
    }
}
