<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCallCenterSettingRequest;
use App\Http\Requests\UpdateCallCenterSettingRequest;
use App\Models\CallCenterSetting;
use App\Http\Resources\CallCenterSettingResource;

class CallCenterSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = CallCenterSetting::all();
        return CallCenterSettingResource::collection($settings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCallCenterSettingRequest $request)
    {
        $setting = CallCenterSetting::create($request->validated());
        return new CallCenterSettingResource($setting);
    }

    /**
     * Display the specified resource.
     */
    public function show(CallCenterSetting $callCenterSetting)
    {
        return new CallCenterSettingResource($callCenterSetting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCallCenterSettingRequest $request, CallCenterSetting $callCenterSetting)
    {
        $callCenterSetting->update($request->validated());
        return new CallCenterSettingResource($callCenterSetting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CallCenterSetting $callCenterSetting)
    {
        $callCenterSetting->delete();
        return response()->json(null, 204);
    }
}
