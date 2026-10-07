<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAreaRequest;
use App\Http\Requests\Api\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return AreaResource::collection(Area::query()->orderBy('name')->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAreaRequest $request)
    {
        $area = Area::create($request->validated());

        return new AreaResource($area);
    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area)
    {
        return new AreaResource($area);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAreaRequest $request, Area $area)
    {
        $area->update($request->validated());

        return new AreaResource($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        $area->delete();

        return response()->noContent();
    }
}
