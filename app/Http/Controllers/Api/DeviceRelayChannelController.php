<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AckDeviceRelayChannelRequest;
use App\Http\Resources\DeviceRelayChannelResource;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints que consulta el propio ESP32 (autenticado con el token del
 * dispositivo, via middleware verify.device.token) para saber si el panel
 * pidió encender/apagar alguno de sus 5 relevadores, y para confirmar que
 * ya aplicó el cambio.
 */
class DeviceRelayChannelController extends Controller
{
    /**
     * Lista el estado deseado de los 5 canales de este dispositivo.
     */
    public function index(Device $device)
    {
        return DeviceRelayChannelResource::collection($device->ensureRelayChannels());
    }

    /**
     * El ESP32 confirma el estado que realmente aplicó en el relevador.
     */
    public function ack(AckDeviceRelayChannelRequest $request, Device $device, int $channel): JsonResponse
    {
        $relayChannel = $device->ensureRelayChannels()->firstWhere('channel', $channel);

        if (! $relayChannel) {
            abort(404, 'Canal de relevador inválido.');
        }

        $relayChannel->update([
            'reported_state' => $request->input('state'),
            'reported_at' => now(),
        ]);

        return (new DeviceRelayChannelResource($relayChannel))
            ->response()
            ->setStatusCode(200);
    }
}
