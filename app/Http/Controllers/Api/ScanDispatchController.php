<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Jobs\AdvantaSmsJob;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStatusTimestamp;
use App\Models\Status;
use App\Services\AdvantaSmsService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\MessageTemplateService;


class ScanDispatchController extends Controller
{
    public function __construct(protected AdvantaSmsService $smsService) {}

    /**
     * Look up a single order by tracking number.
     * Only returns the order if it is currently in "Awaiting Dispatch" status.
     *
     * GET /api/v1/orders/by-tracking/{tracking}
     */
    public function findByTracking(string $tracking)
    {
        $order = Order::with([
            'vendor',
            'customer',
            'shippingAddress.city',
            'shippingAddress.zone',
            'assignments.user',
            'latestStatus.status',
        ])
            // ->where('tracking_number', $tracking)
            ->where('order_no', $tracking)

            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => "No order found with tracking number: {$tracking}",
            ], 404);
        }

        // Only allow orders that are "Awaiting Dispatch"
        $awaitingStatus = Status::where('name', 'Awaiting Dispatch')->first();

        if (
            $awaitingStatus &&
            optional($order->latestStatus)->status_id !== $awaitingStatus->id
        ) {
            return response()->json([
                'success' => false,
                'message' => "Order {$tracking} is not in 'Awaiting Dispatch' status. Current status: "
                    . optional(optional($order->latestStatus)->status)->name,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatOrder($order),
        ]);
    }

    /**
     * Dispatch one or more scanned orders.
     *
     * POST /api/v1/orders/dispatch
     *
     * Body:
     * {
     *   "order_ids":   [1, 2, 3],
     *   "city_from":   1,
     *   "zone_from":   1,       // nullable
     *   "city_to":     2,
     *   "zone_to":     2,       // nullable
     *   "rider_id":    5,
     *   "courrier_id": null     // nullable
     * }
     */
    // public function dispatch(Request $request)
    // {


    //         $templateService = app(MessageTemplateService::class);

    //     $validated = $request->validate([
    //         'order_ids'   => ['required', 'array', 'min:1'],
    //         'order_ids.*' => ['integer', 'exists:orders,id'],
    //         'city_from'   => ['nullable', 'integer', 'exists:cities,id'],
    //         'zone_from'   => ['nullable', 'integer', 'exists:zones,id'],
    //         'city_to'     => ['required', 'integer', 'exists:cities,id'],
    //         'zone_to'     => ['nullable', 'integer', 'exists:zones,id'],
    //         'rider_id'    => ['required', 'integer', 'exists:users,id'],
    //         'courrier_id' => ['nullable', 'integer'],
    //     ]);


    //     $city_from = 1;

    //     // Determine inbound vs outbound:
    //     // Same city  → "Dispatched / In Transit (Inbound)"
    //     // Diff city  → "In Transit (Outbound)"
    //     $isOutbound = (int) $city_from !== (int) $validated['city_to'];

    //     $dispatchedStatus = $this->resolveStatus($isOutbound
    //         ? 'In transit'
    //         : 'Dispatched');

    //     if (! $dispatchedStatus) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Dispatch status not configured in the system.',
    //         ], 500);
    //     }

    //     $orders = Order::with(['assignments', 'customer', 'shippingAddress'])
    //         ->whereIn('id', $validated['order_ids'])
    //         ->get();

    //     $dispatched = [];
    //     $failed     = [];

    //     DB::beginTransaction();
    //     try {
    //         foreach ($orders as $order) {
    //             try {
    //                 // 1. Record new status timestamp
    //                 OrderStatusTimestamp::create([
    //                     'order_id'           => $order->id,
    //                     'status_id'          => $dispatchedStatus->id,
    //                     'status_category_id' => $dispatchedStatus->status_category_id ?? null,
    //                     'status_notes'       => $isOutbound
    //                         ? 'Order dispatched — outbound (inter-city)'
    //                         : 'Order dispatched to delivery agent',
    //                 ]);

    //                 // 2. Ensure rider assignment exists / update it
    //                 OrderAssignment::updateOrCreate(
    //                     [
    //                         'order_id' => $order->id,
    //                         'role'     => 'Delivery Agent',
    //                     ],
    //                     [
    //                         'user_id' => $validated['rider_id'],
    //                         'status'  => 'in_progress',
    //                     ]
    //                 );



    //                 // ✅ Generate message using template + order_id
    //                 $result = $templateService->generateMessage(
    //                     phone: $phone,
    //                     templateSlug: $statusTemplateMap[$statusId],
    //                     additionalData: [
    //                         'order_id' => $orderId
    //                     ]
    //                 );

    //                 // 3. Send SMS to customer



    //                 AdvantaSmsJob::dispatch(
    //                     //   recipient phone number

    //                     //   message content
    //                     // userid
    //                 );



    //                 $dispatched[] = $order->id;
    //             } catch (\Throwable $e) {
    //                 Log::error("Dispatch failed for order #{$order->id}: " . $e->getMessage());
    //                 $failed[] = [
    //                     'order_id' => $order->id,
    //                     'reason'   => $e->getMessage(),
    //                 ];
    //             }
    //         }

    //         DB::commit();
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         Log::error('Dispatch transaction failed: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Dispatch failed: ' . $e->getMessage(),
    //         ], 500);
    //     }

    //     return response()->json([
    //         'success'    => true,
    //         'message'    => count($dispatched) . ' order(s) dispatched successfully.',
    //         'dispatched' => $dispatched,
    //         'failed'     => $failed,
    //     ]);
    // }



    public function dispatch(Request $request)
    {
        $templateService = app(MessageTemplateService::class);

        $validated = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'city_from'   => ['nullable', 'integer', 'exists:cities,id'],
            'zone_from'   => ['nullable', 'integer', 'exists:zones,id'],
            'city_to'     => ['required', 'integer', 'exists:cities,id'],
            'zone_to'     => ['nullable', 'integer', 'exists:zones,id'],
            'rider_id'    => ['required', 'integer', 'exists:users,id'],
            'courrier_id' => ['nullable', 'integer'],
        ]);

        $city_from = $validated['city_from'] ?? 1;

        $isOutbound = (int) $city_from !== (int) $validated['city_to'];

        // $templateSlug = $isOutbound ? 'In transit' : 'Dispatched';

        // only proceed with intransit 

        if ($isOutbound) {
            $templateSlug = 'In transit';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'message will be sent later for',
            ], 422);
        }


        $dispatchedStatus = $this->resolveStatus($isOutbound ? 'In transit' : 'Dispatched');

        if (! $dispatchedStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Dispatch status not configured in the system.',
            ], 500);
        }

        $orders = Order::with(['assignments', 'customer', 'shippingAddress'])
            ->whereIn('id', $validated['order_ids'])
            ->get();

        $dispatched = [];
        $failed     = [];

        DB::beginTransaction();
        try {
            foreach ($orders as $order) {
                try {
                    // 1. Record new status timestamp
                    OrderStatusTimestamp::create([
                        'order_id'           => $order->id,
                        'status_id'          => $dispatchedStatus->id,
                        'status_category_id' => $dispatchedStatus->status_category_id ?? null,
                        'status_notes'       => $isOutbound
                            ? 'Order dispatched — outbound (inter-city)'
                            : 'Order dispatched to delivery agent',
                    ]);

                    // 2. Ensure rider assignment exists / update it
                    OrderAssignment::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'role'     => 'Delivery Agent',
                        ],
                        [
                            'user_id' => $validated['rider_id'],
                            'status'  => 'in_progress',
                        ]
                    );

                    // 3. Resolve customer phone
                    $phone = $order->customer?->phone
                        ?? $order->customer?->alt_phone
                        ?? null;

                    if (! $phone) {
                        Log::warning("No phone found for order #{$order->id}, skipping SMS.");
                        $dispatched[] = $order->id;
                        continue;
                    }

                    // 4. Generate message using template + order_id
                    $result = $templateService->generateMessage(
                        phone: $phone,
                        templateSlug: $templateSlug,
                        additionalData: [
                            'order_id' => $order->id,
                        ]
                    );

                    // 5. Dispatch SMS job
                    AdvantaSmsJob::dispatch(
                        $phone,
                        $result['message'],
                        // $validated['rider_id']   // userId — the acting rider/user

                        // pass user_id 1
                        1,
                    );

                    $dispatched[] = $order->id;
                } catch (\Throwable $e) {
                    Log::error("Dispatch failed for order #{$order->id}: " . $e->getMessage());
                    $failed[] = [
                        'order_id' => $order->id,
                        'reason'   => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Dispatch transaction failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Dispatch failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'    => true,
            'message'    => count($dispatched) . ' order(s) dispatched successfully.',
            'dispatched' => $dispatched,
            'failed'     => $failed,
        ]);
    }

    /**
     * Fetch paginated orders that are currently "Awaiting Dispatch".
     * These are displayed in the main Scan & Dispatch table.
     *
     * GET /api/v1/scan-dispatch/orders
     */
    public function index(Request $request)
    {
        $awaitingStatus = Status::where('name', 'Awaiting Dispatch')->first();

        $query = Order::with([
            'vendor',
            'customer',
            'shippingAddress.city',
            'shippingAddress.zone',
            'assignments.user',
            'latestStatus.status',
        ]);

        // Filter to only awaiting-dispatch orders via latest status
        if ($awaitingStatus) {
            $query->whereHas('latestStatus', function ($q) use ($awaitingStatus) {
                $q->where('status_id', $awaitingStatus->id);
            });
        }

        // Optional rider filter
        if ($request->filled('rider_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('role', 'Delivery Agent')
                    ->where('user_id', $request->rider_id);
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 200);
        $orders  = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $orders->through(fn($order) => $this->formatOrder($order)),
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function resolveStatus(string $slug): ?Status
    {
        return Status::where('name', $slug)->first()
            ?? Status::where('name', 'like', '%' . str_replace('-', ' ', $slug) . '%')->first();
    }

    private function sendDispatchSms(Order $order, int $riderId): void
    {
        $phone = optional($order->shippingAddress)->phone
            ?? optional($order->customer)->phone;

        if (! $phone) {
            return;
        }

        $customerName = optional($order->shippingAddress)->full_name
            ?? optional($order->customer)->full_name
            ?? 'Customer';

        $rider = \App\Models\User::find($riderId);
        $riderName = optional($rider)->name ?? 'our delivery agent';

        $message = $this->buildDispatchMessage($order, $customerName, $riderName);

        try {
            $this->smsService->send($phone, $message);
        } catch (\Throwable $e) {
            Log::warning("SMS dispatch notification failed for order #{$order->id}: " . $e->getMessage());
            // Non-fatal — don't re-throw; dispatch should still succeed
        }
    }

    private function buildDispatchMessage(Order $order, string $customerName, string $riderName): string
    {
        return "Dear {$customerName}, your order #{$order->order_no} has been dispatched "
            . "and is on its way with {$riderName}. "
            . "Track your delivery or contact us for assistance. Thank you for shopping with us!";
    }

    private function formatOrder(Order $order): array
    {
        $deliveryAgent = $order->assignments
            ->firstWhere('role', 'Delivery Agent');

        return [
            'id'              => $order->id,
            'order_no'        => $order->order_no,
            'reference'       => $order->reference,
            'tracking_number' => $order->tracking_number,
            'total_price'     => $order->total_price,
            'delivery_date'   => $order->delivery_date,
            'vendor'          => optional($order->vendor)->only(['id', 'name']),
            'customer'        => [
                'id'        => optional($order->customer)->id,
                'full_name' => optional($order->shippingAddress)->full_name
                    ?? optional($order->customer)->full_name,
                'phone'     => optional($order->shippingAddress)->phone
                    ?? optional($order->customer)->phone,
                'city'      => optional(optional($order->shippingAddress)->city)->name
                    ?? optional(optional($order->customer)->city)->name,
                'zone'      => optional(optional($order->shippingAddress)->zone)->name
                    ?? optional(optional($order->customer)->zone)->name,
            ],
            'latest_status'   => [
                'name'  => optional(optional($order->latestStatus)->status)->name,
                'color' => optional(optional($order->latestStatus)->status)->color,
                'notes' => optional($order->latestStatus)->status_notes,
                'at'    => optional($order->latestStatus)->timestamp,
            ],
            'delivery_agent'  => $deliveryAgent ? [
                'id'   => optional($deliveryAgent->user)->id,
                'name' => optional($deliveryAgent->user)->name,
            ] : null,
        ];
    }
}
