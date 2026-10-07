<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnomalyResource extends JsonResource
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
            'consumption_reading' => new ConsumptionReadingResource($this->whenLoaded('consumptionReading')),
            'z_score' => $this->z_score,
            'value' => $this->value,
            'notified_at' => $this->notified_at,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
