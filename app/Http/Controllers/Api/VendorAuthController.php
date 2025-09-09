<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;



class VendorAuthController extends Controller
{
    /**
     * Issue a Bearer token for a vendor
     */
    public function getToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Ensure only vendors can use this endpoint
        if (! $user->hasRole('Vendor')) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized as vendor'
            ], 403);
        }

        $token = $user->createToken('vendor-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
