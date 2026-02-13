<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Requests\StorevendorServicesRequest;
use App\Http\Requests\UpdatevendorServicesRequest;
use App\Models\vendorServices;


use App\Models\Vendor;
use App\Models\Service;
use App\Models\VendorService;
use App\Models\ServiceRate;
use App\Models\ServiceCondition;
use Illuminate\Http\Request;

class VendorServicesController extends Controller
{






    // complete serice assignement 
    public function assignService(Request $request, Vendor $vendor)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'vendor_id' => 'required|exists:users,id',
        ]);

        return VendorService::firstOrCreate([
            'vendor_id' => $request->vendor_id,
            'service_id' => $request->service_id,
        ]);
    }
    // remove a service from a vendor



    public function removeService($vendorId, $vendorServiceId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $vendorService = VendorService::findOrFail($vendorServiceId);

        logger()->debug('Attempting to remove vendor service', [
            'vendor_id' => $vendor->id,
            'vendor_service_id' => $vendorService->id,
            'vendor_service' => $vendorService->toArray(),
        ]);

        try {
            $vendorService->delete();

            logger()->info('Vendor service removed', [
                'vendor_id' => $vendor->id,
                'vendor_service_id' => $vendorService->id,
            ]);

            return response()->json(['message' => 'Removed']);
        } catch (\Throwable $e) {
            logger()->error('Failed to remove vendor service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $vendor->id,
                'vendor_service_id' => $vendorService->id,
            ]);

            return response()->json(['message' => 'Failed to remove'], 500);
        }
    }



    public function getServices(Request $request, $id)
    {
        return VendorService::with([
            'service',
            // 'service.serviceConditions',
            // 'rates.condition'
        ])
            ->where('vendor_id', $id)
            ->get();
    }


    public function index(Vendor $vendor)
    {
        return VendorService::with([
            'service',
            'rates.condition'
        ])
            ->where('vendor_id', $vendor)
            ->get();
    }

    public function store(Request $request, Vendor $vendor)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        return VendorService::firstOrCreate([
            'vendor_id' => $vendor->id,
            'service_id' => $request->service_id,
        ]);
    }

    public function destroy(Vendor $vendor, VendorService $vendorService)
    {
        $vendorService->delete();
        return response()->json(['message' => 'Removed']);
    }

    public function upsertRate(Request $request, Vendor $vendor, VendorService $vendorService)
    {
        $request->validate([
            'service_condition_id' => 'required|exists:service_conditions,id',
            'custom_rate' => 'nullable|numeric',
            'rate_type' => 'nullable|in:fixed,percentage',
        ]);

        return ServiceRate::updateOrCreate(
            [
                'vendor_service_id' => $vendorService->id,
                'service_condition_id' => $request->service_condition_id,
            ],
            [
                'custom_rate' => $request->custom_rate,
                'rate_type' => $request->rate_type,
            ]
        );
    }
}
