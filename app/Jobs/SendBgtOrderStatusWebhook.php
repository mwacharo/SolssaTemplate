<?php

namespace App\Jobs;

use App\Models\OrderStatusTimestamp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendBgtOrderStatusWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 4;

    /**
     * Retry delays:
     *
     * Attempt 1 -> 1 minute
     * Attempt 2 -> 5 minutes
     * Attempt 3 -> 30 minutes
     */
    public array $backoff = [
        60,
        300,
        1800,
    ];

    public function __construct(
        public int $statusTimestampId
    ) {
        Log::info('BGT Webhook Job Created', [
            'status_timestamp_id' => $statusTimestampId,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('BGT Webhook Job Started', [
            'status_timestamp_id' => $this->statusTimestampId,
            'attempt' => $this->attempts(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Load Status Timestamp
        |--------------------------------------------------------------------------
        */

        $statusTimestamp = OrderStatusTimestamp::with([
            'status',
            'order.customer',
            'order.zone',
            'order.orderItems.product',
        ])->find($this->statusTimestampId);

        if (!$statusTimestamp) {
            Log::error('BGT Webhook: Status timestamp not found', [
                'status_timestamp_id' => $this->statusTimestampId,
            ]);

            return;
        }

        Log::info('BGT Webhook: Status timestamp loaded', [
            'status_timestamp_id' => $statusTimestamp->id,
            'status' => $statusTimestamp->status?->name,
            'created_at' => $statusTimestamp->created_at,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Load Order
        |--------------------------------------------------------------------------
        */

        $order = $statusTimestamp->order;

        if (!$order) {
            Log::error('BGT Webhook: Order not found', [
                'status_timestamp_id' => $statusTimestamp->id,
            ]);

            return;
        }

        Log::info('BGT Webhook: Order loaded', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Get Status
        |--------------------------------------------------------------------------
        */

        $status = $statusTimestamp->status?->name;

        if (!$status) {
            Log::error('BGT Webhook: Status is missing', [
                'status_timestamp_id' => $statusTimestamp->id,
                'order_id' => $order->id,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Build Items
        |--------------------------------------------------------------------------
        */

        $items = $order->orderItems
            ->map(function ($item) {
                return [
                    'sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price * $item->quantity,
                ];
            })
            ->values()
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | 5. Build Payload
        |--------------------------------------------------------------------------
        */

        $payload = [
            'order_no' => $order->order_no,

            'bgt_order_id' => $order->id,

            'status' => $status,

            'updated_at' => $statusTimestamp->created_at
                ?->toISOString(),

            'total' => $order->total_price,

            'items' => $items,

            'customer' => [
                'full_name' => $order->customer?->full_name,

                'phone' => $order->customer?->phone
                    ?? $order->customer_phone,

                'address' => $order->customer?->address,

                // 'region' => $order->customer?->city?->name
                //     ?? $order->zone?->name,
                'region' =>
                $order->customer?->city?->name,
                $order->customer?->zone?->name

                // ?? $order->zone?->name,

                // 'country' => $order->customer?->country?->name
                //     ?? $order->country?->name,
            ],
        ];

        Log::info('BGT Webhook Payload Prepared', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'status' => $status,
            'payload' => $payload,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 6. Get Webhook Configuration
        |--------------------------------------------------------------------------
        */

        // coming from .env file
        $url = env('BGT_WEBHOOK_URL');
        $token = env('BGT_WEBHOOK_SECRET');
        // 'vendor_id' => env('BGT_VENDOR_ID'),




        // $url = config('services.bgt.webhook_url');

        // $token = config('services.bgt.webhook_secret');

        if (empty($url)) {
            Log::error('BGT Webhook URL is missing', [
                'config_key' => 'services.bgt.webhook_url',
            ]);

            throw new RuntimeException(
                'BGT webhook URL is not configured.'
            );
        }

        if (empty($token)) {
            Log::error('BGT Webhook Secret is missing', [
                'config_key' => 'services.bgt.webhook_secret',
            ]);

            throw new RuntimeException(
                'BGT webhook secret is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Build Webhook URL
        |--------------------------------------------------------------------------
        */

        $webhookUrl = $url . '?token=' . urlencode($token);

        Log::info('BGT Webhook Sending Request', [
            'url' => $url,
            'token_present' => !empty($token),
            'order_no' => $order->order_no,
            'bgt_order_id' => $order->id,
            'status' => $status,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 8. Send HTTP Request
        |--------------------------------------------------------------------------
        */

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->asJson()
                ->post(
                    $webhookUrl,
                    $payload
                );
        } catch (Throwable $exception) {

            Log::error('BGT Webhook HTTP Request Exception', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Log Response
        |--------------------------------------------------------------------------
        */

        Log::info('BGT Webhook Response Received', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'response_body' => $response->body(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 10. Successful Response
        |--------------------------------------------------------------------------
        */

        if ($response->status() === 200) {
            Log::info('BGT Webhook Delivered Successfully', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $status,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | 11. Authentication Failure
        |--------------------------------------------------------------------------
        */

        if ($response->status() === 403) {
            Log::error('BGT Webhook Authentication Failed', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'status_code' => 403,
                'response_body' => $response->body(),
            ]);

            throw new RuntimeException(
                'BGT webhook authentication failed with HTTP 403.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 12. Other HTTP Failures
        |--------------------------------------------------------------------------
        */

        Log::error('BGT Webhook Failed', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'status_code' => $response->status(),
            'response_body' => $response->body(),
            'attempt' => $this->attempts(),
        ]);

        throw new RuntimeException(
            'BGT webhook failed with HTTP ' .
                $response->status()
        );
    }

    /**
     * Handle a job failure after all retries.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('BGT Webhook Job Permanently Failed', [
            'status_timestamp_id' => $this->statusTimestampId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }
}
