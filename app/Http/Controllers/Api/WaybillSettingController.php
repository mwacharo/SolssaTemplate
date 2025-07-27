<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWaybillSettingRequest;
use App\Http\Requests\UpdateWaybillSettingRequest;
use App\Models\WaybillSetting;
use Illuminate\Http\JsonResponse;

class WaybillSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $settings = WaybillSetting::all();
        return response()->json(['data' => $settings], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWaybillSettingRequest $request): JsonResponse
    {
        $waybillSetting = WaybillSetting::create($request->validated());
        return response()->json(['data' => $waybillSetting], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WaybillSetting $waybillSetting): JsonResponse
    {
        return response()->json(['data' => $waybillSetting], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWaybillSettingRequest $request, WaybillSetting $waybillSetting): JsonResponse
    {
        $waybillSetting->update($request->validated());
        return response()->json(['data' => $waybillSetting], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WaybillSetting $waybillSetting): JsonResponse
    {
        $waybillSetting->delete();
        return response()->json(null, 204);
    }
}
