<?php

namespace App\Services;

use App\Services\ConversionEvent;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Http;

class ConversionDispatcher
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    //



    public function dispatch(ConversionEvent $event, array $config, $order): void
    {
        $this->sendMeta($event, $config['meta'] ?? []);
        // $this->sendTikTok($event, $config['tiktok'] ?? []);

        $this->sendTikTok($event, $config['tiktok'] ?? [], $order);
        $this->sendGoogle($event, $config['google'] ?? []);
    }






    private function sendMeta($event, array $config): void
    {
        if (!($config['enabled'] ?? false)) return;
        if (!$config['pixel'] || !$config['access_token']) return;

        $customData = ['order_id' => (string) $event->orderId];

        if ($event->eventName === 'Purchase') {
            $customData['value']    = (float) $event->value;
            $customData['currency'] = 'KES'; // ✅ hardcoded — never from $event->currency
        }

        $response = Http::post(
            "https://graph.facebook.com/v19.0/{$config['pixel']}/events",
            [
                'access_token' => $config['access_token'],
                'data' => [[
                    'event_name'    => $event->eventName,
                    'event_time'    => time(),
                    'event_id'      => (string) $event->eventId,
                    'action_source' => 'website',

                    'user_data' => array_filter([
                        'client_user_agent' => request()->userAgent(),
                        'client_ip_address' => request()->ip(),
                        'em'  => $event->email ? hash('sha256', strtolower(trim($event->email))) : null,
                        'ph'  => $event->phone ? hash('sha256', preg_replace('/\D+/', '', $event->phone)) : null,
                        'fbp' => request()->cookie('_fbp'),
                        'fbc' => request()->cookie('_fbc'),
                    ]),

                    'custom_data' => $customData,
                ]]
            ]
        );

        Log::info('Meta response', ['status' => $response->status(), 'body' => $response->json()]);
    }




    private function sendTikTok(ConversionEvent $event, array $config, $order): void
    {
        if (!($config['enabled'] ?? false)) return;
        if (!$config['pixel'] || !$config['access_token']) return; // guard 
        $customer = $order->customer;
        $item = $order->order_items[0] ?? null;

        $payload = [
            'pixel_code' => $config['pixel'],
            'event'      => $event->eventName,
            'event_id'   => $event->eventId,
            'timestamp'  => now()->toIso8601String(),
            'context' => [
                'user' => array_filter([
                    'phone_number' => $customer->phone
                        ? hash('sha256', preg_replace('/\D+/', '', $customer->phone))
                        : null,
                    'email' => $customer->email
                        ? hash('sha256', strtolower(trim($customer->email)))
                        : null,
                ]),
            ],

            'properties' => [
                'value'        => (float) $order->total_price,
                // 'currency'     => 'KES',
                'content_type' => 'product',
                'contents'     => $item ? [[
                    'content_id'   => (string) $item->product_id,
                    'content_name' => $item->product->product_name ?? $item->sku,
                    'quantity'     => (int) $item->quantity,
                    'price'        => (float) $item->unit_price,
                ]] : [],
            ],
        ];

        Log::info('TikTok Config', [
            'pixel' => $config['pixel'],
            'token_present' => !empty($config['access_token']),
        ]);

        // $response = Http::withHeaders([
        //     'Access-Token' => $config['access_token'],
        // ])->post(
        //     'https://business-api.tiktok.com/open_api/v1.3/pixel/track/',
        // );


        $response = Http::withHeaders([
            'Access-Token' => $config['access_token'],
            'Content-Type' => 'application/json',
        ])->post(
            'https://business-api.tiktok.com/open_api/v1.3/pixel/track/',
            $payload
        );

        Log::info('TikTok response', $response->json());

        // log the payload for debugging
        Log::info('TikTok payload', $payload);
    }

    private function sendGoogle($event, array $config): void
    {
        if (!($config['enabled'] ?? false)) return;

        // Placeholder for Google Ads API
        Log::info('Google conversion sent', [
            'conversion_id' => $config['conversion_id'],
            'event' => $event->eventName,
        ]);
    }
}
