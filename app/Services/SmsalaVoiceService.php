<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsalaVoiceService
{
    protected string $endpoint = 'https://api2.smsala.com/api/VoiceBridge';

    protected string $apiToken = 'Ri8A5WRKLddUdphQ';
    protected string $callerNumber = '919906121146';
    protected string $callbackUrl = 'logibrain.solssa.com/api/v1/africastalking-handle-callback';

    public function bridgeCall(string $calledNumber): array
    {
        $response = Http::timeout(20)->get($this->endpoint, [
            'apiToken'     => $this->apiToken,
            'callerNumber' => $this->callerNumber,
            'calledNumber' => $calledNumber,
            'callBackUrl'  => $this->callbackUrl,
        ]);

        if (! $response->successful()) {
            throw new \Exception('SMSala error: ' . $response->body());
        }

        return [
            'callId' => uniqid('smsala_'),
            'raw'    => $response->json()
        ];
    }
}
