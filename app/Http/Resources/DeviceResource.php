<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'name' => $this->name,
            'status' => $this->status,
            'last_reading_at' => $this->last_reading_at,
            'area' => new AreaResource($this->whenLoaded('area')),
            'device_type' => new DeviceTypeResource($this->whenLoaded('deviceType')),
            'device_model' => new DeviceModelResource($this->whenLoaded('deviceModel')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
