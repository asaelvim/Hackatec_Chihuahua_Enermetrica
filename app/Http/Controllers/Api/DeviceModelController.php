<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceModelRequest;
use App\Http\Requests\Api\UpdateDeviceModelRequest;
use App\Http\Resources\DeviceModelResource;
use App\Models\DeviceModel;

class DeviceModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DeviceModelResource::collection(DeviceModel::query()->orderBy('name')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeviceModelRequest $request)
    {
        $deviceModel = DeviceModel::create($request->validated());

        return new DeviceModelResource($deviceModel);
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceModel $deviceModel)
    {
        return new DeviceModelResource($deviceModel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeviceModelRequest $request, DeviceModel $deviceModel)
    {
        $deviceModel->update($request->validated());

        return new DeviceModelResource($deviceModel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceModel $deviceModel)
    {
        $deviceModel->delete();

        return response()->noContent();
    }
}
