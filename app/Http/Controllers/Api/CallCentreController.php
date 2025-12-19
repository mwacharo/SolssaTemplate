<?php

// namespace App\Http\Controllers;

namespace App\Http\Controllers\Api;

use App\Events\AgentStatusUpdated;
use App\Http\Controllers\Controller;

use App\Http\Requests\StoreCallCentreRequest;
use App\Http\Requests\UpdateCallCentreRequest;
use App\Models\CallCentre;
use App\Models\User;
use App\Services\AfricasTalkingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SmsalaVoiceService;

use Illuminate\Http\JsonResponse;

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

            // return response()->json([
            //     'success' => true,
            //     'data' => $result
            //     // 'data' => $result['data'] ?? []

            // ]);


            return $result;
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
            $validated = $request->validate([
                'status' => 'required|string|max:50'
            ]);

            Log::debug('updateAgentStatus called', [
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            $agent = auth()->user();

            Log::debug('Agent found', [
                'agent_id' => $agent->id,
                'current_status' => $agent->status
            ]);

            $agent->update([
                'status' => trim($validated['status']),
                'last_seen_at' => now()

            ]);

            Log::debug('Agent status updated', [
                'agent_id' => $agent->id,
                'new_status' => $agent->status
            ]);


            broadcast(new AgentStatusUpdated($agent))->toOthers();

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



    public function ping()
    {
        $agent = auth()->user();

        $agent->update(['last_seen_at' => now()]);

        // Optionally re-broadcast "still online" if needed
        return response()->json(['success' => true]);
    }



    public function makeCall(
        Request $request,
        SmsalaVoiceService $smsala
    ): JsonResponse {
        $request->validate([
            'phoneNumber' => ['required', 'string']
        ]);

        $user = $request->user();

        /**
         * This endpoint is ONLY hit when:
         * - Africa's Talking client is NOT available
         * - e.g Zambia
         */

        if ($user->country_id !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Call centre API not enabled for this country'
            ], 403);
        }


        // add some logs 
        Log::info('Making call via SmsalaVoiceService', [
            'user_id' => $user->id,
            'phone_number' => $request->phoneNumber
        ]);
        $call = $smsala->bridgeCall($request->phoneNumber);

        return response()->json([
            'success' => true,
            'callId'  => $call['callId'] ?? uniqid('call_'),
            'status'  => 'connecting'
        ]);
    }

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
