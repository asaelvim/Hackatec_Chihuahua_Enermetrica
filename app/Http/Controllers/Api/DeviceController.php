<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceRequest;
use App\Http\Requests\Api\UpdateDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DeviceResource::collection(
            Device::query()->with(['area', 'deviceType', 'deviceModel', 'controller'])->orderBy('name')->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceRequest $request)
    {
        $device = Device::create($request->validated());

        return new DeviceResource($device->load(['area', 'deviceType', 'deviceModel', 'controller']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        return new DeviceResource($device->load(['area', 'deviceType', 'deviceModel', 'controller']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceRequest $request, Device $device)
    {
        $device->update($request->validated());

        return new DeviceResource($device->load(['area', 'deviceType', 'deviceModel', 'controller']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Device $device)
    {
        $device->delete();

        return response()->noContent();
    }

    /**
     * Genera un nuevo api_token para el dispositivo, invalidando el anterior.
     */
    public function regenerateToken(Device $device)
    {
        $device->forceFill(['api_token' => Str::random(40)])->save();

        return response()->json(['api_token' => $device->api_token]);
    }

    /**
     * Enciende/apaga con un clic (usado por la app móvil y el panel web).
     * No hace nada si el dispositivo está "offline"/"maintenance".
     */
    public function toggleStatus(Request $request, Device $device)
    {
        $device->toggleStatus($request->user());

        return new DeviceResource($device->fresh(['area', 'deviceType', 'deviceModel', 'controller']));
    }
}
