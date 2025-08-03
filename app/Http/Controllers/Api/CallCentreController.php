<?php

// namespace App\Http\Controllers;

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreCallCentreRequest;
use App\Http\Requests\UpdateCallCentreRequest;
use App\Models\CallCentre;
use App\Services\AfricasTalkingService;
use Illuminate\Support\Facades\Log;

class CallCentreController extends Controller
{


    /**
     * Generate tokens for users by calling AfricasTalkingService.
     */
    public function getToken()
    {
        try {
            // Assuming you have injected AfricasTalkingService via the constructor or use app() helper
            $africasTalkingService = app(\App\Services\AfricasTalkingService::class);

            $result = $africasTalkingService->generateTokens();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Token generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Token generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCallCentreRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CallCentre $callCentre)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CallCentre $callCentre)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCallCentreRequest $request, CallCentre $callCentre)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CallCentre $callCentre)
    {
        //
    }
}
