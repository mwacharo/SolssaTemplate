<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\StoreWaybillSettingRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Http\Requests\UpdateWaybillSettingRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Models\WaybillSetting;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Country::all();
        return CountryResource::collection($countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $country = Country::create($request->validated());
        return new CountryResource($country);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return new CountryResource($country);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, $id)
    {
        $country = Country::findOrFail($id);
        $country->update($request->validated());
        return new CountryResource($country);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        // Log the entire request
        Log::info('Destroy Country Request', [
            'request' => request()->all(),
            'country_id' => $country->id,
        ]);

        $country->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getSettings($id)
    {
        $country = Country::findOrFail($id);
        $settings = $country->waybillSettings; // Assuming a relationship exists
        return response()->json($settings);
    } 
    
    

        /**
     * Store waybill settings for a specific country
     */
    // public function storeSettings(StoreWaybillSettingRequest $request, $id): JsonResponse
    // {
    //     $country = Country::findOrFail($id);
        
    //     // Check if settings already exist for this country
    //     $existingSettings = WaybillSetting::where('country_id', $id)->first();
    //     if ($existingSettings) {
    //         return response()->json([
    //             'message' => 'Waybill settings already exist for this country. Use PUT to update.',
    //             'data' => $existingSettings
    //         ], 409);
    //     }
        
    //     // Add country_id to validated data
    //     $validatedData = $request->validated();
    //     $validatedData['country_id'] = $id;
        
    //     $waybillSetting = WaybillSetting::create($validatedData);
    //     return response()->json(['data' => $waybillSetting], 201);
    // }


    public function storeSettings(StoreWaybillSettingRequest $request, $id): JsonResponse
{
    $country = Country::findOrFail($id);

    $validatedData = $request->validated();
    $validatedData['country_id'] = $id;

    // Update if exists, create if not
    $waybillSetting = WaybillSetting::updateOrCreate(
        ['country_id' => $id], // condition
        $validatedData          // values to update or create
    );

    return response()->json([
        'message' => 'Waybill settings saved successfully.',
        'data' => $waybillSetting
    ], 200);
}


    /**
     * Update waybill settings for a specific country
     */
    public function updateSettings(UpdateWaybillSettingRequest $request, $id): JsonResponse
    {
        $country = Country::findOrFail($id);

        $validatedData = $request->validated();
        $validatedData['country_id'] = $id;

        $settings = WaybillSetting::updateOrCreate(
            ['country_id' => $id],
            $validatedData
        );

        return response()->json(['data' => $settings], 200);
    }

    /**
     * Delete waybill settings for a specific country
     */
    public function destroySettings($id): JsonResponse
    {
        $country = Country::findOrFail($id);
        
        $settings = WaybillSetting::where('country_id', $id)->first();
        
        if (!$settings) {
            return response()->json([
                'message' => 'No waybill settings found for this country',
                'data' => null
            ], 404);
        }
        
        $settings->delete();
        return response()->json(null, 204);
    }
}
