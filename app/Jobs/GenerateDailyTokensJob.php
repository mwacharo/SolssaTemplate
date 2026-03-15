<?php

namespace App\Jobs;

use App\Services\AfricasTalkingService;
// use App\Services\YourServiceClass; // <-- Update with your actual service class name
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


use App\Models\Country;
use App\Models\UserCountryAccount;

class GenerateDailyTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // public function handle()
    // {
    //     try {
    //         $service = app(AfricasTalkingService::class);
    //         $result = $service->generateTokens();

    //         Log::info('Daily AfricaTalking WebRTC tokens generated', [
    //             'updated' => $result['totalUpdated'],
    //             'failed'  => $result['totalFailed'],
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Daily token generation failed: ' . $e->getMessage());
    //     }
    // }




    public function handle()
    {
        try {
            $countries = Country::where('status', 1)->get();

            foreach ($countries as $country) {

                $service = app(AfricasTalkingService::class);

                // Load credentials for THIS country
                $service->loadCountryConfig($country->id);

                // Get users belonging to this country
                $userIds = UserCountryAccount::where('country_id', $country->id)
                    ->pluck('user_id')
                    ->toArray();

                if (empty($userIds)) {
                    continue;
                }

                $result = $service->generateTokens($userIds);

                Log::info("Daily tokens generated for country {$country->name}", [
                    'country_id' => $country->id,
                    'updated'    => $result['totalUpdated'],
                    'failed'     => $result['totalFailed'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Daily token generation failed: ' . $e->getMessage());
        }
    }
}
