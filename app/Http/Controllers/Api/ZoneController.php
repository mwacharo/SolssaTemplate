<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Models\Zone;
use Illuminate\Http\Response;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zones = Zone::with(['country', 'city'])->paginate(300);
        return ZoneResource::collection($zones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreZoneRequest $request)
    {
        $zone = Zone::create($request->validated());
        return new ZoneResource($zone);
    }

    /**
     * Display the specified resource.
     */
    public function show(Zone $zone)
    {
        return new ZoneResource($zone);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateZoneRequest $request, $id)
    {
        $zone = Zone::findOrFail($id);
        $zone->update($request->validated());
        return new ZoneResource($zone);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
