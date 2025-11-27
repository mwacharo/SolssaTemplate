<?php

namespace App\Services\Zoho;

use Illuminate\Support\Facades\Http;

class ZohoMailService
{
    protected $auth;

    public function __construct(ZohoAuthService $auth)
    {
        $this->auth = $auth;
    }

    public function sendEmail($to, $subject, $content)
    {
        $accessToken = $this->auth->getAccessToken();

        $accountId = $this->getAccountId($accessToken);

        $response = Http::withToken($accessToken)
            ->post("https://mail.zoho.com/api/accounts/{$accountId}/messages", [
                "fromAddress" => env('MAIL_FROM_ADDRESS'),
                "toAddress"   => $to,
                "subject"     => $subject,
                "content"     => $content,
            ]);

        return $response->json();
    }

    protected function getAccountId($token)
    {
        $resp = Http::withToken($token)
            ->get('https://mail.zoho.com/api/accounts');

        return $resp->json()['data'][0]['accountId'];
    }
}
