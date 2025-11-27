<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Http;

class ZohoAuthService
{
    public function getAccessToken()
    {
        // Hit Zoho token endpoint
        $response = Http::asForm()->post(
            'https://accounts.zoho.com/oauth/v2/token',
            [
                'refresh_token' => env('ZOHO_REFRESH_TOKEN'),
                'client_id' => env('ZOHO_CLIENT_ID'),
                'client_secret' => env('ZOHO_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
            ]
        );

        // return $response->json()['access_token'];
            dd($response->json()); // <— ADD THIS

    }
}
