<?php

namespace App\Jobs;

use App\Services\AfricasTalkingService;
use App\Services\YourServiceClass; // <-- Update with your actual service class name
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDailyTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $service = app(AfricasTalkingService::class);
            $result = $service->generateTokens();

            Log::info('Daily AfricaTalking WebRTC tokens generated', [
                'updated' => $result['totalUpdated'],
                'failed'  => $result['totalFailed'],
            ]);

        } catch (\Exception $e) {
            Log::error('Daily token generation failed: ' . $e->getMessage());
        }
    }
}
