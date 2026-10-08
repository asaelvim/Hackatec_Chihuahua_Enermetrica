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
            'channel' => $this->relay_channel,
            'label' => $this->name,
            // Si el dispositivo esta offline/mantenimiento se le pide al
            // ESP32 que lo deje apagado, aunque "status" guarde ese valor.
            'desired_state' => in_array($this->status, ['on', 'off'], true) ? $this->status : 'off',
            'reported_state' => $this->reported_status,
            'commanded_at' => $this->commanded_at,
            'reported_at' => $this->reported_at,
        ];
    }
}
