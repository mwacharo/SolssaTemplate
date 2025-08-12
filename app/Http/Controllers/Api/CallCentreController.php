<?php

// namespace App\Http\Controllers;

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreCallCentreRequest;
use App\Http\Requests\UpdateCallCentreRequest;
use App\Models\CallCentre;
use App\Models\User;
use App\Services\AfricasTalkingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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


    public function handleVoiceCallback()
    {
        try {
            // Assuming you have injected AfricasTalkingService via the constructor or use app() helper
            $africasTalkingService = app(\App\Services\AfricasTalkingService::class);

            $result = $africasTalkingService->handleVoiceCallback(request());

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Voice callback handling failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Voice callback handling failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function handleEventCallback()
    {
        try {
            // Assuming you have injected AfricasTalkingService via the constructor or use app() helper
            $africasTalkingService = app(\App\Services\AfricasTalkingService::class);

            $result = $africasTalkingService->handleEventCallback(request());

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('SMS callback handling failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'SMS callback handling failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function fetchCallHistory()
    {
        try {
            // Assuming you have injected AfricasTalkingService via the constructor or use app() helper
            $africasTalkingService = app(\App\Services\AfricasTalkingService::class);

            $result = $africasTalkingService->fetchCallHistory();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch call history failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Fetch call history failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateAgentStatus(Request $request)
    {
        try {
            Log::debug('updateAgentStatus called', [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $agent = User::where('id', auth()->id())->firstOrFail();

            Log::debug('Agent found', [
                'agent_id' => $agent->id,
                'current_status' => $agent->status
            ]);

            $agent->update(['status' => $request->status]);

            Log::debug('Agent status updated', [
                'agent_id' => $agent->id,
                'new_status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agent status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Update agent status failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Update agent status failed',
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
