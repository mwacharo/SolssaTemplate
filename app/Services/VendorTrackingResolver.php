<?php

namespace App\Services;

use App\Models\Order;

class VendorTrackingResolver
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    // }


    public function resolve(Order $order): array
    {
        $vendor = $order->vendor;

        return [
            'meta' => [
                'enabled' => true,
                // 'pixel' => $vendor->meta_pixel_id,
                // 'access_token' => $vendor->meta_access_token ?? null,

                'pixel' => env('META_PIXEL_ID'), // env variable for testing
                'access_token' => env('META_ACCESS_TOKEN'), // env variable for testing

                // lets had code access token for testing 
                // 'access_token' => 'test_access_token',
            ],

            'tiktok' => [
                'enabled' => true,
                // 'pixel' => $vendor->tiktok_pixel_code,
                'pixel' => env('TIKTOK_PIXEL_ID'), // env variable for testing
                // 'access_token' => $vendor->tiktok_access_token ?? null, // ADD 
                'access_token' => env('TIKTOK_ACCESS_TOKEN'),


            ],

            'google' => [
                'enabled' => true,
                'conversion_id' => $vendor->google_conversion_id,
            ],
        ];
    }
}
