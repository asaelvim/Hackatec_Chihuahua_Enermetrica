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
     * Lista el estado deseado de los dispositivos controlados por los 5
     * relevadores de este dispositivo.
     */
    public function index(Device $device)
    {
        return DeviceRelayChannelResource::collection($device->relayDevices);
    }

    /**
     * El ESP32 confirma el estado que realmente aplicó en el relevador.
     */
    public function ack(AckDeviceRelayChannelRequest $request, Device $device, int $channel): JsonResponse
    {
        $relayDevice = $device->relayDevices()->where('relay_channel', $channel)->first();

        if (! $relayDevice) {
            abort(404, 'Canal de relevador inválido.');
        }

        $relayDevice->update([
            'reported_status' => $request->input('state'),
            'reported_at' => now(),
        ]);

        return (new DeviceRelayChannelResource($relayDevice))
            ->response()
            ->setStatusCode(200);
    }
}
