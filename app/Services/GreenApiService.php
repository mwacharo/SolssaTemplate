<?php 
namespace App\Services;

use Illuminate\Support\Facades\Http;

class GreenApiService
{
    protected $instanceId;
    protected $apiToken;
    protected $apiUrl;

    public function __construct()
    {
        $this->instanceId = config('services.greenapi.instance_id');
        $this->apiToken = config('services.greenapi.api_token');
        $this->apiUrl = config('services.greenapi.url', 'https://api.green-api.com');
    }

    public function getInstanceState()
    {
        $url = "{$this->apiUrl}/waInstance{$this->instanceId}/getStateInstance/{$this->apiToken}";

        $response = Http::get($url);

        return $response->successful()
            ? $response->json()
            : ['error' => true, 'message' => $response->body()];
    }




    public function sendMessage(string $phone, string $message)
    {
        $url = "{$this->apiUrl}/waInstance{$this->instanceId}/sendMessage/{$this->apiToken}";

        $response = Http::post($url, [
            'chatId' => $this->formatPhone($phone),
            'message' => $message,
        ]);

        return $response->successful()
            ? $response->json()
            : ['error' => true, 'message' => $response->body()];
    }

    private function formatPhone(string $phone): string
    {
        return str_contains($phone, '@c.us') ? $phone : $phone . '@c.us';
    }
}
