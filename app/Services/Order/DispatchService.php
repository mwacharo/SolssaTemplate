<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\OrderStatusTimestamp;
use App\Models\Status;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\AdvantaSmsService;
use App\Jobs\AdvantaSmsJob;
use App\Services\TemplateService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class DispatchService
{
    /**
     * Dispatch a batch of orders.
     *
     * - Sets city_from, zone_from, city_to, zone_to, rider_id, courrier_id
     * - Stamps dispatched_at
     * - Appends a "Dispatched" status history entry for each order
     *
     * @param  array $orderIds
     * @param  array $payload  { city_from, zone_from, city_to, zone_to, rider_id, courrier_id }
     * @return int  Number of orders dispatched
     */
    public function dispatch(array $orderIds, array $payload): int
    {
        $dispatchedStatus = Status::findBySlug(Status::DISPATCHED);

        if (! $dispatchedStatus) {
            throw new \RuntimeException('Order status "Dispatched" not found. Please seed the order_statuses table.');
        }

        $count = 0;

        DB::transaction(function () use ($orderIds, $payload, $dispatchedStatus, &$count) {
            // Fetch only orders that are currently "Awaiting Dispatch"
            $orders = Order::whereIn('id', $orderIds)
                ->awaitingDispatch()
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                // Update routing & assignment
                $order->update([
                    'city_from_id'  => $payload['city_from'],
                    'zone_from_id'  => $payload['zone_from'] ?? null,
                    'city_to_id'    => $payload['city_to'],
                    'zone_to_id'    => $payload['zone_to'] ?? null,
                    'rider_id'      => $payload['rider_id'],
                    'courrier_id'   => $payload['courrier_id'] ?? null,
                    'dispatched_at' => now(),
                ]);

                // 3. Resolve customer phone
                $phone = $order->customer?->phone
                    ?? $order->customer?->alt_phone
                    ?? null;

                if (! $phone) {
                    Log::warning("No phone found for order #{$order->id}, skipping SMS.");
                    $dispatched[] = $order->id;
                    continue;
                }

                $dispatched[] = $order->id;
                $count++;
            }
        });

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Move selected dispatched orders to "In Transit".
    // ─────────────────────────────────────────────────────────────────────────
    public function sendToInTransit(array $orderIds): int
    {
        $inTransitStatus = Status::where('name', 'In Transit')->first();

        if (! $inTransitStatus) {
            throw new \RuntimeException('Status "In Transit" not found.');
        }

        $count = 0;

        DB::transaction(function () use ($orderIds, $inTransitStatus, &$count) {
            $orders = Order::whereIn('id', $orderIds)
                ->whereHas(
                    'latestStatus',
                    fn($q) => $q->whereHas(
                        'status',
                        fn($q2) => $q2->where('name', 'Dispatched')
                    )
                )
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                OrderStatusTimestamp::create([
                    'order_id'     => $order->id,
                    'status_id'    => $inTransitStatus->id,
                    'status_notes' => 'Sent to In Transit from Dispatch List',
                ]);

                $count++;
            }
        });

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/dispatched  —  main listing used by the Vue filter panel
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'dispatched_on' => 'nullable|date',
            'shipped_on'    => 'nullable|date',
        ]);

        // At least one date is required (mirrors frontend warning)
        if (! $request->dispatched_on && ! $request->shipped_on) {
            return response()->json(['message' => 'At least one date filter is required.'], 422);
        }

        $orders = $this->buildBaseQuery($request)
            ->with([
                'customer.city',
                'customer.zone',
                'vendor:id,name',
                'latestStatus.status',
                'orderItems',
                'shippingAddress',
                'statusTimestamps.status',
                'assignments.user', // ← source of truth for delivery man
            ])
            ->paginate(25);

        return response()->json([
            'data'       => $orders->map(fn($o) => $this->formatOrder($o)),
            'pagination' => [
                'from'  => $orders->firstItem(),
                'to'    => $orders->lastItem(),
                'total' => $orders->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF
    // ─────────────────────────────────────────────────────────────────────────
    public function generateDispatchPDF(Request $request)
    {
        Log::info('Generating dispatch PDF', $request->only([
            'order_ids',
            'dispatched_on',
            'shipped_on',
            'city_to',
            'zone_to',
            'delivery_man',
            'seller',
            'courrier',
            'status',
            'agent_name',
        ]));

        // Single query for the delivery man — used for both name and company lookup
        $deliveryman = User::find($request->input('delivery_man'));
        $company     = $this->getCompanyDetails($deliveryman?->country_id);

        Log::info('Company details for PDF', $company);

        $orders = $this->buildBaseQuery($request)
            ->with([
                'customer.city',
                'customer.zone',
                'vendor:id,name',
                'orderItems.product',
                'shippingAddress',
                'latestStatus.status',
                'statusTimestamps.status',
                'assignments.user',
            ])
            ->get();


        // DEBUG — remove after confirming
        // $debugBase = Order::whereIn('id', $request->order_ids)->first();
        // Log::info('Order raw data', [
        //     'order_id'        => $debugBase?->id,
        //     'status_timestamps' => $debugBase?->statusTimestamps->map(fn($t) => [
        //         'status'     => $t->status?->name,
        //         'created_at' => $t->created_at,
        //         'date_only'  => $t->created_at->toDateString(),
        //     ]),
        //     'assignments' => $debugBase?->assignments->map(fn($a) => [
        //         'user_id'     => $a->user_id,
        //         'courrier_id' => $a->courrier_id,
        //     ]),
        // ]);

        Log::info('Retrieved orders for PDF', ['count' => $orders->count()]);

        // log the order json 
        Log::info('Orders JSON', $orders->toArray());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.dispatchlist', [
            'orders'    => $orders,
            'agentName' => $deliveryman?->name ?? 'N/A',
            'filters'   => $this->resolveFilterLabels($request),
            'company'   => $company,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('dispatch_' . now()->format('Ymd_His') . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Excel
    // ─────────────────────────────────────────────────────────────────────────
    public function generateDispatchExcel(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new DispatchExport($request, $this->resolveFilterLabels($request)),
            'dispatch_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/v1/orders/dispatch  —  scan & dispatch dialog submission
    // ─────────────────────────────────────────────────────────────────────────
    public function dispatchOrders(Request $request)
    {
        $request->validate([
            'order_ids'   => 'required|array',
            'order_ids.*' => 'integer|exists:orders,id',
            'city_from'   => 'required|exists:cities,id',
            'city_to'     => 'required|exists:cities,id',
            'rider_id'    => 'required|exists:users,id',
            'zone_from'   => 'nullable|exists:zones,id',
            'zone_to'     => 'nullable|exists:zones,id',
            'courrier_id' => 'nullable|exists:courriers,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->order_ids as $orderId) {
                $order = Order::findOrFail($orderId);

                // Assign rider
                $order->update(['rider_id' => $request->rider_id]);

                // Log dispatched status
                $dispatchedStatus = Status::whereRaw("LOWER(name) = 'dispatched'")->first();
                if ($dispatchedStatus) {
                    $order->statusTimestamps()->create([
                        'status_id' => $dispatchedStatus->id,
                        'user_id'   => auth()->id(),
                        'note'      => 'Dispatched via scan & dispatch',
                    ]);
                }

                // Create assignment if courrier provided
                if ($request->courrier_id) {
                    $order->assignments()->create([
                        'courrier_id' => $request->courrier_id,
                        'city_from'   => $request->city_from,
                        'zone_from'   => $request->zone_from,
                        'city_to'     => $request->city_to,
                        'zone_to'     => $request->zone_to,
                        'user_id'     => auth()->id(),
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Orders dispatched successfully.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/orders/by-tracking/{tracking}
    // ─────────────────────────────────────────────────────────────────────────
    public function findByTracking(string $tracking)
    {
        $order = Order::where('reference', $tracking)
            ->with([
                'vendor:id,name',
                'customer',
                'shippingAddress',
                'latestStatus.status',
                'orderItems',
            ])
            ->first();

        if (! $order) {
            return response()->json(['message' => "No order found for tracking: {$tracking}"], 404);
        }

        return response()->json([
            'data' => [
                'id'               => $order->id,
                'vendor'           => ['name' => $order->vendor?->name],
                'shipping_address' => ['full_name' => $order->shippingAddress?->full_name],
                'customer'         => ['full_name' => $order->customer?->full_name],
                'delivery_date'    => $order->delivery_date?->format('d M Y'),
                'total_price'      => $order->total_price,
                'latest_status'    => ['status' => ['name' => $order->latestStatus?->status?->name]],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Build the shared base query from request filters
    // ─────────────────────────────────────────────────────────────────────────
    private function buildBaseQuery(Request $request)
    {
        $query = Order::query();

        // Scope to explicit order_ids if provided (PDF / Excel download)
        if ($request->filled('order_ids')) {
            $query->whereIn('id', $request->order_ids);
        }

        // ── Date filters via OrderStatusTimestamp ─────────────────────────
        if ($request->filled('dispatched_on')) {
            $query->whereHas('statusTimestamps', function ($q) use ($request) {
                $q->whereHas('status', fn($s) => $s->whereRaw('LOWER(name) = ?', ['dispatched']))
                    ->whereDate('created_at', $request->dispatched_on);
            });
        }

        if ($request->filled('shipped_on')) {
            $query->whereHas('statusTimestamps', function ($q) use ($request) {
                $q->whereHas('status', fn($s) => $s->whereRaw("LOWER(REPLACE(name, ' ', '_')) = 'in_transit'"))
                    ->whereDate('created_at', $request->shipped_on);
            });
        }

        // ── City / Zone (via customer) ────────────────────────────────────
        if ($request->filled('city_to')) {
            $query->whereHas('customer', fn($q) => $q->where('city_id', $request->city_to));
        }

        if ($request->filled('zone_to')) {
            $query->whereHas('customer', fn($q) => $q->where('zone_id', $request->zone_to));
        }

        // ── Rider — source of truth is assignments.user_id ────────────────
        if ($request->filled('delivery_man')) {
            $query->whereHas('assignments', fn($q) => $q->where('user_id', $request->delivery_man));
        }

        // ── Vendor ────────────────────────────────────────────────────────
        if ($request->filled('seller')) {
            $query->where('vendor_id', $request->seller);
        }

        // ── Courrier (via assignments) ────────────────────────────────────
        if ($request->filled('courrier')) {
            $query->whereHas('assignments', fn($q) => $q->where('courrier_id', $request->courrier));
        }

        // ── Status ────────────────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->whereHas('latestStatus.status', fn($q) => $q->where('id', $request->status));
        }

        // ── Search (order_no, customer name, phone, address) ──────────────
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', $search)
                    ->orWhereHas('customer', fn($cq) => $cq
                        ->where('full_name', 'like', $search)
                        ->orWhere('phone', 'like', $search))
                    ->orWhereHas('shippingAddress', fn($aq) => $aq->where('address', 'like', $search));
            });
        }

        return $query->latest();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Resolve filter IDs → human-readable labels for PDF/Excel header
    // ─────────────────────────────────────────────────────────────────────────
    private function resolveFilterLabels(Request $request): array
    {
        $labels = [];

        if ($date = $request->input('dispatched_on')) {
            $labels['Dispatched On'] = \Carbon\Carbon::parse($date)->format('d M Y');
        }

        if ($date = $request->input('shipped_on')) {
            $labels['Shipped On'] = \Carbon\Carbon::parse($date)->format('d M Y');
        }

        if ($id = $request->input('city_to')) {
            $labels['City To'] = \App\Models\City::find($id)?->name;
        }

        if ($id = $request->input('zone_to')) {
            $labels['Zone To'] = \App\Models\Zone::find($id)?->name;
        }

        if ($id = $request->input('delivery_man')) {
            $labels['Delivery Man'] = \App\Models\User::find($id)?->name;
        }

        if ($id = $request->input('seller')) {
            $labels['Seller'] = \App\Models\User::find($id)?->name;
        }

        if ($id = $request->input('status')) {
            $labels['Status'] = \App\Models\Status::find($id)?->name;
        }

        return array_filter($labels);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Format a single order for the Vue table
    // ─────────────────────────────────────────────────────────────────────────
    private function formatOrder(Order $order): array
    {
        $dispatchedAt = $order->statusTimestamps
            ->filter(fn($t) => strtolower($t->status?->name ?? '') === 'dispatched')
            ->sortByDesc('created_at')
            ->first();

        return [
            'id'             => $order->id,
            'orderNo'        => $order->order_no,
            'customer'       => $order->customer?->full_name ?? '-',
            'address'        => $order->shippingAddress?->address
                ?? $order->customer?->address ?? '-',
            'vendor'         => $order->vendor?->name ?? '-',
            'details'        => $order->orderItems->pluck('product_name')->implode(', '),
            'dispatchedOn'   => $dispatchedAt?->created_at->format('d M Y') ?? '-',
            'cityTo'         => $order->customer?->city?->name ?? '-',
            'zoneTo'         => $order->customer?->zone?->name ?? '-',
            // Source of truth: assignments.user_id (consistent with buildBaseQuery filter)
            'deliveryMan'    => $order->assignments->first()?->user?->name ?? '-',
            'totalPrice'     => number_format($order->total_price, 2),
            'status'         => $order->latestStatus?->status?->name ?? '-',
            'trackingNumber' => $order->reference ?? '-',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE: Resolve company details from delivery man's country
    // ─────────────────────────────────────────────────────────────────────────
    private function getCompanyDetails($countryId): array
    {
        $country = Country::with('waybillSettings')->find($countryId);
        $waybill = $country?->waybillSettings;

        return [
            'name'    => $waybill?->name      ?? 'COURIER AND FULFILLMENT SERVICES',
            'phone'   => $waybill?->phone     ?? '',
            'email'   => $waybill?->email     ?? '',
            'address' => $waybill?->address   ?? '',
            'logo'    => $waybill?->logo_path ?? null,
            'footer'  => $waybill?->footer    ?? '',
            'terms'   => $waybill?->terms     ?? '',
        ];
    }
}
