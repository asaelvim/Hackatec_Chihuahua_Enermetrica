<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceRelayChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'channel' => $this->channel,
            'label' => $this->label,
            'desired_state' => $this->desired_state,
            'reported_state' => $this->reported_state,
            'commanded_at' => $this->commanded_at,
            'reported_at' => $this->reported_at,
        ];
    }
}
