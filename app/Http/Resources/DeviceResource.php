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
            'is_togglable' => $this->isTogglable(),
            'is_relay_controlled' => $this->isRelayControlled(),
            'controller_device_id' => $this->controller_device_id,
            'controller_name' => $this->whenLoaded('controller', fn () => $this->controller?->name),
            'relay_channel' => $this->relay_channel,
            'reported_status' => $this->reported_status,
            'last_reading_at' => $this->last_reading_at,
            'area' => new AreaResource($this->whenLoaded('area')),
            'device_type' => new DeviceTypeResource($this->whenLoaded('deviceType')),
            'device_model' => new DeviceModelResource($this->whenLoaded('deviceModel')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
