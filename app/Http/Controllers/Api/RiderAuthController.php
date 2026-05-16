<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RiderAuthController extends Controller
{

    public function getToken(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // log the request for debugging
        Log::info('Rider login attempt', [
            'email' => $request->email,
            'ip'    => $request->ip(),
        ]);

        $user = User::where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();

        // Check credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Ensure only riders can use this endpoint
        if (! $user->hasRole('Delivery Agent')) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized as rider'
            ], 403);
        }

        // Block inactive riders
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account inactive. Contact admin.'
            ], 403);
        }

        // Update login tracking — your users table has these columns
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_seen_at'  => now(),
        ]);

        $token = $user->createToken('rider-api-token')->plainTextToken;

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone_number'    => $user->phone_number,
                'status'          => $user->status,
                'is_active'       => $user->is_active,
                'current_team_id' => $user->current_team_id,
            ]
        ]);
    }

    /**
     * Revoke the rider's current token (logout)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Update rider online/offline status
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,offline,busy'
        ]);

        $request->user()->update([
            'status'       => $request->status,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => $request->status
        ]);
    }
}
