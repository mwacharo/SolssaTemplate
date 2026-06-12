<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\AdvantaSmsJob;
// use App\Models\NotificationLog;
use App\Models\Template;
use App\Services\MessageTemplateService;
// use App\Services\AdvantaSmsService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationsListener implements ShouldQueue
{
    use InteractsWithQueue;

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    public int $tries = 3;

    public int $timeout = 120;

    /*
    |--------------------------------------------------------------------------
    | Allowed Statuses
    |--------------------------------------------------------------------------
    */

    protected array $allowedStatuses = [
        'pending',
        'scheduled',
        'in transit',
        'delivered',
    ];

    public function __construct(
        protected MessageTemplateService $templateService,
        // protected AdvantaSmsService $smsService,
        protected WhatsAppService $whatsappService
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $payload = $event->payload;

        $status = strtolower($payload['status'] ?? '');

        if (!in_array($status, $this->allowedStatuses)) {
            return;
        }

        $order = $payload['order'] ?? [];

        $orderId = $order['id'] ?? null;

        $countryId = $payload['country_id'] ?? null;

        $phone = $payload['customer_phone'] ?? null;

        if (!$orderId || !$phone) {
            return;
        }

        foreach (['sms', 'whatsapp'] as $channel) {

            /*
            |--------------------------------------------------------------------------
            | Cached Template Lookup
            |--------------------------------------------------------------------------
            */

            $cacheKey = implode('_', [
                'template',
                $channel,
                $status,
                $countryId,
            ]);

            $template = Cache::remember(
                $cacheKey,
                now()->addHours(1),
                function () use (
                    $channel,
                    $status,
                    $countryId
                ) {
                    return Template::query()
                        // ->where('module', 'order_status')
                        // ->where('channel', $channel)
                        ->where('name', $status)
                        ->where(function ($query) use ($countryId) {
                            $query
                                ->where('country_id', $countryId)
                                ->orWhereNull('country_id');
                        })
                        ->orderByRaw('country_id IS NULL')
                        ->first();
                }
            );

            if (!$template) {

                Log::warninchannelg(
                    'Notification template missing',
                    [
                        'channel' => $channel,
                        'status' => $status,
                    ]
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Generate Message
            |--------------------------------------------------------------------------
            */

            $result = $this->templateService->generateMessage(
                phone: $phone,
                templateId: $template->id,
                additionalData: [
                    'order_id' => $orderId,
                    'status' => $status,
                ]
            );

            $message = $result['message'] ?? null;

            if (!$message) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Send Notification
            |--------------------------------------------------------------------------
            */

            try {

                $providerResponse = null;

                if ($channel === 'sms') {

                    Log::info('Sending Advanta SMS', [
                        'phone' => $phone,
                        'user_id' => $payload['user_id'] ?? null,
                    ]);


                    // $userId = $payload['user_id'] ?? null;

                    // $userId = 1;
                    $userId = \App\Models\User::first()?->id;

                    if (!$userId) {
                        Log::warning('SMS skipped: missing user_id', [
                            'order_id' => $orderId,
                            'phone' => $phone,
                        ]);
                    } else {
                        AdvantaSmsJob::dispatch(
                            $phone,
                            $message,
                            $userId
                        );
                    }
                }

                if ($channel === 'whatsapp') {

                    $providerResponse = $this->whatsappService->sendMessage(
                        $phone,
                        $message,
                        // $payload['user_id'] ?? null,  // userId — null if not in payload
                        \App\Models\User::first()?->id,  // userId — using first user as fallback
                        $orderId                       // orderId — already extracted above
                    );
                }

                // NotificationLog::create([
                //     'order_id' => $orderId,
                //     'channel' => $channel,
                //     'status' => $status,
                //     'recipient' => $phone,
                //     'message' => $message,
                //     'provider_response' => is_array($providerResponse)
                //         ? json_encode($providerResponse)
                //         : $providerResponse,
                //     'success' => true,
                //     'sent_at' => now(),
                // ]);
            } catch (\Throwable $e) {

                // NotificationLog::create([
                //     'order_id' => $orderId,
                //     'channel' => $channel,
                //     'status' => $status,
                //     'recipient' => $phone,
                //     'message' => $message,
                //     'provider_response' => $e->getMessage(),
                //     'success' => false,
                //     'sent_at' => now(),
                // ]);

                Log::error(
                    'Notification sending failed',
                    [
                        'order_id' => $orderId,
                        'channel' => $channel,
                        'status' => $status,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }
}
