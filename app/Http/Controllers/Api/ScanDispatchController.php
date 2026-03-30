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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\MessageTemplateService;

use App\Services\Order\DispatchService;


// 
// TODO: Verify this class exists at app/Services/Order/DispatchService.php


class ScanDispatchController extends Controller
{
    public function __construct(
        protected AdvantaSmsService $smsService,
        protected DispatchService $dispatchService

    ) 
    {}

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


    // // orders dispatched or in transit



    // public function dispatched(Request $request): JsonResponse
    // {
    //     // ── Require at least one date filter ──────────────────────────────────────
    //     if (! $request->filled('dispatchedOn') && ! $request->filled('shippedOn')) {
    //         return response()->json([
    //             'message' => 'At least one date filter (dispatchedOn or shippedOn) is required.',
    //         ], 422);
    //     }

    //     // ── Resolve the IDs for "Dispatched" and "In Transit" statuses ────────────
    //     // "dispatched_at" doesn't exist as a column — we derive it from
    //     // OrderStatusTimestamp rows whose status name is Dispatched or In Transit.
    //     $dispatchedStatusIds = Status::whereIn('name', [
    //         'Dispatched',
    //         'In Transit',
    //     ])->pluck('id');

    //     if ($dispatchedStatusIds->isEmpty()) {
    //         return response()->json([
    //             'message' => 'Could not resolve Dispatched / In Transit status IDs. Check order_statuses table.',
    //         ], 500);
    //     }

    //     // ── Base query: only orders that have at least one Dispatched/In-Transit
    //     //    status timestamp ──────────────────────────────────────────────────────
    //     $query = Order::query()
    //         ->with([
    //             // vendor & rider are both Users
    //             'vendor:id,name',
    //             'rider:id,name,phone',

    //             // customer carries city, zone, address, phone
    //             'customer:id,full_name,phone,city_id,zone_id,address',
    //             'customer.city:id,name',
    //             'customer.zone:id,name',

    //             // order-level zone (zone_id on orders table)
    //             'zone:id,name',

    //             // latest status timestamp + its parent status
    //             'latestStatus.status:id,name',
    //         ])
    //         // ->whereHas('statusTimestamps', function ($q) use ($dispatchedStatusIds) {
    //         //     $q->whereIn('status_id', $dispatchedStatusIds);
    //         // });

    //         // ✅ NEW — "latest status IS dispatched/in-transit right now"
    //         ->whereHas(
    //             'latestStatus',
    //             fn($q) =>
    //             $q->whereIn('status_id', $dispatchedStatusIds)
    //         );

    //     // ── Full-text search (id, order_no, customer name, phone, address) ────────
    //     if ($request->filled('search')) {
    //         $term = '%' . $request->search . '%';
    //         $query->where(function ($q) use ($term) {
    //             $q->where('orders.id', 'like', $term)
    //                 ->orWhere('order_no', 'like', $term)
    //                 ->orWhereHas('customer', function ($cq) use ($term) {
    //                     $cq->where('name', 'like', $term)
    //                         ->orWhere('phone', 'like', $term)
    //                         ->orWhere('address', 'like', $term);
    //                 });
    //         });
    //     }

    //     // ── Dropdown filters ──────────────────────────────────────────────────────

    //     // CITY TO  — city lives on the customer
    //     if ($request->filled('cityTo')) {
    //         $query->whereHas(
    //             'customer',
    //             fn($q) =>
    //             $q->where('city_id', $request->cityTo)
    //         );
    //     }

    //     // ZONE TO — zone lives on the customer
    //     if ($request->filled('zoneTo')) {
    //         $query->whereHas(
    //             'customer',
    //             fn($q) =>
    //             $q->where('zone_id', $request->zoneTo)
    //         );
    //     }

    //     // DELIVERY MAN — rider_id on orders
    //     if ($request->filled('deliveryMan')) {
    //         $query->whereHas(
    //             'assignments',
    //             fn($q) =>
    //             $q->where('role', 'Delivery Agent')
    //                 ->where('user_id', $request->deliveryMan)
    //         );
    //     }

    //     // COURRIER — courrier_id on orders (if column exists)
    //     if ($request->filled('courrier')) {
    //         $query->where('courrier_id', $request->courrier);
    //     }

    //     // SELLER — vendor_id on orders
    //     if ($request->filled('seller')) {
    //         $query->where('vendor_id', $request->seller);
    //     }

    //     // STATUS — filter by the *latest* status id
    //     if ($request->filled('status')) {
    //         $query->whereHas(
    //             'latestStatus',
    //             fn($q) =>
    //             $q->where('status_id', $request->status)
    //         );
    //     }

    //     // ── Date filters — look inside OrderStatusTimestamp, NOT a column ─────────

    //     // DISPATCHED ON — date when the order first received a "Dispatched" status
    //     if ($request->filled('dispatchedOn')) {
    //         $query->whereHas('statusTimestamps', function ($q) use ($request, $dispatchedStatusIds) {
    //             $q->whereIn('status_id', $dispatchedStatusIds)
    //                 ->whereDate('created_at', $request->dispatchedOn);
    //         });
    //     }

    //     // SHIPPED ON — kept for compatibility; treated the same way
    //     if ($request->filled('shippedOn')) {
    //         $query->whereHas('statusTimestamps', function ($q) use ($request, $dispatchedStatusIds) {
    //             $q->whereIn('status_id', $dispatchedStatusIds)
    //                 ->whereDate('created_at', $request->shippedOn);
    //         });
    //     }

    //     // ── Paginate, ordering by when the order was dispatched (desc) ────────────
    //     // We join the earliest dispatch timestamp so we can order by it cleanly.
    //     $query->orderByDesc(
    //         \App\Models\OrderStatusTimestamp::select('created_at')
    //             ->whereColumn('order_id', 'orders.id')
    //             ->whereIn('status_id', $dispatchedStatusIds)
    //             ->oldest()          // first dispatch event
    //             ->limit(1)
    //     );

    //     $perPage   = (int) $request->get('per_page', 25);
    //     $paginated = $query->paginate($perPage);

    //     // ── Shape rows to match every frontend column ──────────────────────────────
    //     $rows = $paginated->getCollection()->map(function (Order $order) use ($dispatchedStatusIds) {

    //         // The "dispatched on" date = created_at of the first Dispatched/In-Transit
    //         // status timestamp for this order
    //         $dispatchedAt = $order->statusTimestamps
    //             ->whereIn('status_id', $dispatchedStatusIds->all())
    //             ->sortBy('created_at')
    //             ->first()
    //             ?->created_at
    //             ?->format('Y-m-d');

    //         return [
    //             'id'             => $order->id,
    //             'orderNo'        => $order->order_no,
    //             // 'trackingNumber' => $order->reference,         
    //             'customer'       => $order->customer?->full_name,
    //             'address'        => $order->customer?->address,
    //             'seller'         => $order->vendor?->name,
    //             'details'        => $order->customer_notes,
    //             'dispatchedOn'   => $dispatchedAt,
    //             'cityFrom'       => null,
    //             'cityTo'         => $order->customer?->city?->name,
    //             'zoneFrom'       => $order->zone?->name,
    //             'zoneTo'         => $order->customer?->zone?->name,
    //             'deliveryMan'    => $order->assignments
    //                 ->firstWhere('role', 'Delivery Agent')
    //                 ?->user
    //                 ?->name,

    //             'totalPrice'     => number_format((float) $order->total_price, 2),
    //             'status'         => $order->latestStatus?->status?->name ?? '—',
    //         ];
    //     });

    //     return response()->json([
    //         'data'       => $rows,
    //         'pagination' => [
    //             'from'         => $paginated->firstItem() ?? 0,
    //             'to'           => $paginated->lastItem()  ?? 0,
    //             'total'        => $paginated->total(),
    //             'current_page' => $paginated->currentPage(),
    //             'last_page'    => $paginated->lastPage(),
    //             'per_page'     => $paginated->perPage(),
    //         ],
    //     ]);
    // }



    public function dispatched(Request $request): JsonResponse
    {
        // ── Require at least one date filter ──────────────────────────────────────
        if (! $request->filled('dispatchedOn') && ! $request->filled('shippedOn')) {
            return response()->json([
                'message' => 'At least one date filter (dispatchedOn or shippedOn) is required.',
            ], 422);
        }

        // ── Resolve status IDs ────────────────────────────────────────────────────
        $dispatchedStatusIds = Status::whereIn('name', ['Dispatched', 'In transit'])
            ->pluck('id');

        if ($dispatchedStatusIds->isEmpty()) {
            return response()->json([
                'message' => 'Dispatched / In Transit statuses not found.',
            ], 500);
        }

        $date = $request->dispatchedOn ?? $request->shippedOn;

        // ── Base query ────────────────────────────────────────────────────────────
        $query = Order::query()
            ->with([
                'vendor:id,name',
                'rider:id,name,phone',
                'customer:id,full_name,phone,city_id,zone_id,address',
                'customer.city:id,name',
                'customer.zone:id,name',
                'zone:id,name',
                'latestStatus.status:id,name',
                'assignments.user:id,name'
            ])

            // ✅ ONLY orders whose CURRENT (latest) status is dispatched/in-transit
            ->whereHas('latestStatus', function ($q) use ($dispatchedStatusIds, $date) {
                $q->whereIn('status_id', $dispatchedStatusIds)
                    ->whereDate('created_at', $date); // ✅ filter by latest timestamp date
            });

        // ── Search ────────────────────────────────────────────────────────────────
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';

            $query->where(function ($q) use ($term) {
                $q->where('orders.id', 'like', $term)
                    ->orWhere('order_no', 'like', $term)
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('full_name', 'like', $term)
                            ->orWhere('phone', 'like', $term)
                            ->orWhere('address', 'like', $term);
                    });
            });
        }

        // ── Filters ───────────────────────────────────────────────────────────────
        if ($request->filled('cityTo')) {
            $query->whereHas(
                'customer',
                fn($q) =>
                $q->where('city_id', $request->cityTo)
            );
        }

        if ($request->filled('zoneTo')) {
            $query->whereHas(
                'customer',
                fn($q) =>
                $q->where('zone_id', $request->zoneTo)
            );
        }

        if ($request->filled('deliveryMan')) {
            $query->whereHas(
                'assignments',
                fn($q) =>
                $q->where('role', 'Delivery Agent')
                    ->where('user_id', $request->deliveryMan)
            );
        }

        if ($request->filled('courrier')) {
            $query->where('courrier_id', $request->courrier);
        }

        if ($request->filled('seller')) {
            $query->where('vendor_id', $request->seller);
        }

        if ($request->filled('status')) {
            $query->whereHas(
                'latestStatus',
                fn($q) =>
                $q->where('status_id', $request->status)
            );
        }

        // ── Order by latest dispatch timestamp ─────────────────────────────────────
        $query->orderByDesc(
            \App\Models\OrderStatusTimestamp::select('created_at')
                ->whereColumn('order_id', 'orders.id')
                ->whereIn('status_id', $dispatchedStatusIds)
                ->latest()
                ->limit(1)
        );

        // ── Pagination ────────────────────────────────────────────────────────────
        $perPage   = (int) $request->get('per_page', 25);
        $paginated = $query->paginate($perPage);

        // ── Transform ─────────────────────────────────────────────────────────────
        $rows = $paginated->getCollection()->map(function (Order $order) use ($dispatchedStatusIds) {

            // ✅ Get latest dispatched/in-transit timestamp (DB-level, no collection scan)
            $dispatchedAt = \App\Models\OrderStatusTimestamp::where('order_id', $order->id)
                ->whereIn('status_id', $dispatchedStatusIds)
                ->latest('created_at')
                ->value('created_at');

            return [
                'id'           => $order->id,
                'orderNo'      => $order->order_no,
                'customer'     => $order->customer?->full_name,
                'address'      => $order->customer?->address,
                'seller'       => $order->vendor?->name,
                'details'      => $order->customer_notes,
                'dispatchedOn' => optional($dispatchedAt)->format('Y-m-d'),
                'cityFrom'     => null,
                'cityTo'       => $order->customer?->city?->name,
                'zoneFrom'     => $order->zone?->name,
                'zoneTo'       => $order->customer?->zone?->name,
                'deliveryMan'  => $order->assignments
                    ->firstWhere('role', 'Delivery Agent')
                    ?->user
                    ?->name,
                'totalPrice'   => number_format((float) $order->total_price, 2),
                'status'       => $order->latestStatus?->status?->name ?? '—',
            ];
        });

        return response()->json([
            'data' => $rows,
            'pagination' => [
                'from'         => $paginated->firstItem() ?? 0,
                'to'           => $paginated->lastItem() ?? 0,
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ],
        ]);
    }




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



    public function sendToInTransit(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        try {
            $count = $this->dispatchService->sendToInTransit($request->order_ids);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => "{$count} order(s) moved to In Transit.",
            'count'   => $count,
        ]);
    }

    public function downloadDispatchPDF(Request $request)
    {
        $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        return $this->dispatchService->generateDispatchPDF($request->order_ids);
    }

    public function downloadDispatchExcel(Request $request)
    {
        $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        return $this->dispatchService->generateDispatchExcel($request->order_ids);
    }
}
