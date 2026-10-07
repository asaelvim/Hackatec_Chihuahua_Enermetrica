<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceTypeRequest;
use App\Http\Requests\Api\UpdateDeviceTypeRequest;
use App\Http\Resources\DeviceTypeResource;
use App\Models\DeviceType;

class DeviceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DeviceTypeResource::collection(DeviceType::query()->orderBy('name')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceTypeRequest $request)
    {
        $deviceType = DeviceType::create($request->validated());

        return new DeviceTypeResource($deviceType);
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceType $deviceType)
    {
        return new DeviceTypeResource($deviceType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceTypeRequest $request, DeviceType $deviceType)
    {
        $deviceType->update($request->validated());

        return new DeviceTypeResource($deviceType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceType $deviceType)
    {
        $deviceType->delete();

        return response()->noContent();
    }
}
