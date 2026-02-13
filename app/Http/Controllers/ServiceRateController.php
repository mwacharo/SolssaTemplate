<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRateRequest;
use App\Http\Requests\UpdateServiceRateRequest;
use App\Models\ServiceRate;
use Illuminate\Http\JsonResponse;

class ServiceRateController extends Controller
{
    /**
     * Display a listing of overrides.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            ServiceRate::with([
                'vendorService',
                'serviceCondition'
            ])->latest()->paginate(20)
        );
    }

    /**
     * Store or Update Override (UPSERT)
     */
    public function store(StoreServiceRateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rate = ServiceRate::updateOrCreate(
            [
                'vendor_service_id' => $data['vendor_service_id'],
                'service_condition_id' => $data['service_condition_id'],
            ],
            [
                'custom_rate' => $data['custom_rate'],
                'rate_type' => $data['rate_type'],
            ]
        );

        return response()->json([
            'message' => 'Override saved successfully',
            'data' => $rate->load([
                'vendorService',
                'serviceCondition'
            ])
        ], 201);
    }

   
    /**
     * Update override explicitly
     */
    public function update(
        UpdateServiceRateRequest $request,
        ServiceRate $serviceRate
    ): JsonResponse {

        $serviceRate->update($request->validated());

        return response()->json([
            'message' => 'Override updated successfully',
            'data' => $serviceRate->fresh()->load([
                'vendorService',
                'serviceCondition'
            ])
        ]);
    }

    /**
     * Delete override (Revert to default service pricing)
     */
    public function destroy(ServiceRate $serviceRate): JsonResponse
    {
        $serviceRate->delete();

        return response()->json([
            'message' => 'Override removed. Default service pricing will apply.'
        ]);
    }
}
