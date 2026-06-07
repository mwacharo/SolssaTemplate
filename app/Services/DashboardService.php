<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{


    // orders given
    // confirmation summary 
    // delivery summary 


    private function getOrdersGivenByDate($query)
    {
        return $query
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
    }



    private array $confirmationStatuses = [
        'confirmed' => [
            'Scheduled',
            'Awaiting Dispatch',
            'Dispatched',
            'In transit',
            'Delivered',
        ],

        'pending' => [
            'New',
            'Pending',
            'Pending Confirmation',
        ],

        'failed' => [
            'Cancelled',
            'Duplicate',
            // 'Out of Stock',
        ],
    ];





    // public function getConfirmationSummary($query)
    // {
    //     $orders = $query
    //         ->with('latestStatus.status')
    //         ->get();

    //     $total = $orders->count();

    //     $confirmed = 0;
    //     $pending = 0;
    //     $failed = 0;

    //     foreach ($orders as $order) {

    //         $status = optional(
    //             optional($order->latestStatus)->status
    //         )->name;

    //         if (in_array($status, $this->confirmationStatuses['confirmed'])) {
    //             $confirmed++;
    //         } elseif (in_array($status, $this->confirmationStatuses['pending'])) {
    //             $pending++;
    //         } elseif (in_array($status, $this->confirmationStatuses['failed'])) {
    //             $failed++;
    //         }
    //     }

    //     $rate = $total > 0
    //         ? round(($confirmed / $total) * 100, 2)
    //         : 0;

    //     return [
    //         'total_orders' => $total,

    //         'confirmed' => $confirmed,

    //         'pending' => $pending,

    //         'failed' => $failed,

    //         'confirmation_rate' => $rate,
    //     ];
    // }



    public function getConfirmationSummary($query): array
    {
        $orders = $query
            ->with('latestStatus.status')
            ->get();

        $confirmed = 0;
        $pending = 0;
        $failed = 0;

        $dailyTrend = [];

        foreach ($orders as $order) {

            $status = optional(
                optional($order->latestStatus)->status
            )->name;

            $date = $order->created_at->format('Y-m-d');

            if (!isset($dailyTrend[$date])) {
                $dailyTrend[$date] = [
                    'date' => $date,
                    'total_orders' => 0,
                    'confirmed' => 0,
                    'pending' => 0,
                    'failed' => 0,
                ];
            }

            $dailyTrend[$date]['total_orders']++;

            if (in_array($status, $this->confirmationStatuses['confirmed'])) {

                $confirmed++;
                $dailyTrend[$date]['confirmed']++;
            } elseif (in_array($status, $this->confirmationStatuses['pending'])) {

                $pending++;
                $dailyTrend[$date]['pending']++;
            } elseif (in_array($status, $this->confirmationStatuses['failed'])) {

                $failed++;
                $dailyTrend[$date]['failed']++;
            }
        }

        $total = $orders->count();

        return [
            'summary' => [
                'total_orders' => $total,
                'confirmed' => $confirmed,
                'pending' => $pending,
                'failed' => $failed,
                'confirmation_rate' => $total > 0
                    ? round(($confirmed / $total) * 100, 2)
                    : 0,
            ],

            'status_breakdown' => [
                [
                    'status' => 'Confirmed',
                    'count' => $confirmed,
                ],
                [
                    'status' => 'Pending',
                    'count' => $pending,
                ],
                [
                    'status' => 'Failed',
                    'count' => $failed,
                ],
            ],

            'daily_trend' => collect($dailyTrend)
                ->sortBy('date')
                ->values(),
        ];
    }




    public function getDeliverySummary($query)
    {
        $orders = $query
            ->with('latestStatus.status')
            ->get();

        $totalOrders = $orders->count();

        /*
    |--------------------------------------------------------------------------
    | STATUS GROUPS (FULL FLEXIBLE MODEL)
    |--------------------------------------------------------------------------
    */

        $deliveredStatuses = [
            'Delivered',
        ];

        $cancelledStatuses = [
            'Cancelled',
            'Returned',
            'Return Received',
            'Awaiting Return',
            'Undispatched',
        ];

        $shippingStatuses = [
            'Awaiting Dispatch',
            'Dispatched',
            'In transit',
        ];

        $confirmationStatuses = [
            'Scheduled',
            'Pending',
            'Pending Confirmation',
            'New',
        ];

        /*
    |--------------------------------------------------------------------------
    | COUNTERS
    |--------------------------------------------------------------------------
    */

        $delivered = 0;
        $cancelled = 0;
        $shipping = 0;
        $confirmed = 0;

        $statusBreakdown = [];

        foreach ($orders as $order) {

            $statusName = optional(
                optional($order->latestStatus)->status
            )->name;

            if (!$statusName) continue;

            // DELIVERY
            if (in_array($statusName, $deliveredStatuses)) {
                $delivered++;
            }

            // CANCELLED / FAILED
            elseif (in_array($statusName, $cancelledStatuses)) {
                $cancelled++;
            }

            // SHIPPING
            elseif (in_array($statusName, $shippingStatuses)) {
                $shipping++;
            }

            // CONFIRMED / PIPELINE
            elseif (in_array($statusName, $confirmationStatuses)) {
                $confirmed++;
            }

            /*
        |--------------------------------------------------------------------------
        | STATUS BREAKDOWN (FULL DYNAMIC LIST)
        |--------------------------------------------------------------------------
        */
            $statusBreakdown[$statusName] = ($statusBreakdown[$statusName] ?? 0) + 1;
        }

        /*
    |--------------------------------------------------------------------------
    | DELIVERY RATE
    |--------------------------------------------------------------------------
    | You can define it in two ways:
    | - Delivered / Total Orders
    | - Delivered / (Delivered + Cancelled)
    */

        $deliveryRate = ($totalOrders > 0)
            ? round(($delivered / $totalOrders) * 100, 2)
            : 0;

        /*
    |--------------------------------------------------------------------------
    | RETURN
    |--------------------------------------------------------------------------
    */

        return [
            'total_orders' => $totalOrders,

            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'shipping' => $shipping,
            'confirmed' => $confirmed,

            'delivery_rate' => $deliveryRate,

            'status_breakdown' => collect($statusBreakdown)
                ->map(function ($count, $status) {
                    return [
                        'status' => $status,
                        'count' => $count,
                    ];
                })
                ->values(),
        ];
    }



    /**
     * Reusable vendor scope — returns base orders query scoped to vendor if applicable.
     */
    private function ordersQuery($user)
    {
        $query = DB::table('orders')->whereNull('orders.deleted_at');

        if ($this->isVendor($user)) {
            $query->where('orders.vendor_id', $user->id);
        }

        // ✅ Always scope to the user's active country
        if ($user?->country_id) {
            $query->where('orders.country_id', $user->country_id);
        }

        return $query;
    }

    private function isVendor($user): bool
    {
        return in_array($user?->getRoleNames()->first(), ['vendor', 'Vendor']);
    }

    /**
     * Resolves the active country_id for the given user.
     * Returns null if no country is set (no filter applied).
     */
    private function activeCountryId($user): ?int
    {
        return $user?->country_id ? (int) $user->country_id : null;
    }

    /**
     * Builds a reusable SQL WHERE clause fragment for country scoping on orders.
     * Assumes the orders table alias is `o`.
     */
    private function countryWhere($user, string $alias = 'o'): string
    {
        $countryId = $this->activeCountryId($user);
        return $countryId ? "AND {$alias}.country_id = {$countryId}" : "";
    }

    /**
     * Subquery that gets the latest status name per order — reused across methods.
     */
    private function latestStatusSubquery(): string
    {
        return "
            SELECT ost.order_id, s.name as status_name
            FROM order_status_timestamps ost
            INNER JOIN (
                SELECT order_id, MAX(id) as max_id
                FROM order_status_timestamps
                GROUP BY order_id
            ) latest ON ost.id = latest.max_id
            INNER JOIN statuses s ON s.id = ost.status_id
        ";
    }

    /**
     * Get high-level order stats.
     */
    public function getOrderStats($user): array
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $rows = DB::select("
            SELECT 
                ls.status_name,
                COUNT(*) as count
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
            GROUP BY ls.status_name
        ");

        $statusCounts  = collect($rows)->pluck('count', 'status_name');
        $totalOrders   = $statusCounts->sum();
        $delivered     = (int) ($statusCounts['Delivered'] ?? 0);
        $pending       = (int) ($statusCounts['Pending'] ?? 0);
        $deliveryRate  = $totalOrders > 0 ? round(($delivered / $totalOrders) * 100, 2) : 0;
        $monthlyTarget = max($totalOrders + 20, 150);
        $orderProgress = $monthlyTarget > 0 ? min(100, round(($totalOrders / $monthlyTarget) * 100, 2)) : 0;

        return [
            'totalOrders'         => $totalOrders,
            'deliveries'          => $delivered,
            'pending'             => $pending,
            'deliveryRate'        => $deliveryRate,
            'orderGrowth'         => $this->calculateOrderGrowth($user),
            'monthlyTarget'       => $monthlyTarget,
            'orderProgress'       => $orderProgress,
            'averageDeliveryTime' => $this->calculateAverageDeliveryTime($user),
            'statusCounts'        => $statusCounts,
        ];
    }

    /**
     * Order analytics grouped by period.
     */
    public function getOrderAnalytics($user, string $period = '6M'): array
    {
        $now          = Carbon::now();
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        switch ($period) {
            case '1M':
                $groupExpr  = "WEEK(o.created_at)";
                $labels     = collect(range(3, 0))->map(fn($w) => $now->copy()->subWeeks($w)->format('W'));
                $filterExpr = "o.created_at >= '" . $now->copy()->subWeeks(3)->startOfWeek()->toDateTimeString() . "'";
                break;
            case '3M':
                $groupExpr  = "DATE_FORMAT(o.created_at, '%b')";
                $labels     = collect(range(2, 0))->map(fn($m) => $now->copy()->subMonths($m)->format('M'));
                $filterExpr = "o.created_at >= '" . $now->copy()->subMonths(2)->startOfMonth()->toDateTimeString() . "'";
                break;
            case '1Y':
                $groupExpr  = "QUARTER(o.created_at)";
                $labels     = collect([1, 2, 3, 4]);
                $filterExpr = "YEAR(o.created_at) = {$now->year}";
                break;
            case '6M':
            default:
                $groupExpr  = "DATE_FORMAT(o.created_at, '%b')";
                $labels     = collect(range(5, 0))->map(fn($m) => $now->copy()->subMonths($m)->format('M'));
                $filterExpr = "o.created_at >= '" . $now->copy()->subMonths(5)->startOfMonth()->toDateTimeString() . "'";
                break;
        }

        $rows = DB::select("
            SELECT 
                ls.status_name,
                {$groupExpr} as period,
                COUNT(*) as count
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL
            AND {$filterExpr}
            {$vendorWhere} {$countryWhere}
            GROUP BY ls.status_name, {$groupExpr}
        ");

        $grouped = collect($rows)->groupBy('status_name');
        $result  = [];

        foreach ($grouped as $status => $entries) {
            $byPeriod = $entries->pluck('count', 'period');
            $result[] = [
                'name' => $status,
                'data' => $labels->map(fn($label) => (int) ($byPeriod[$label] ?? 0))->values()->toArray(),
            ];
        }

        return $result;
    }

    /**
     * Status overview counts.
     */
    public function getStatusOverview($user): array
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $rows = DB::select("
            SELECT ls.status_name, COUNT(*) as count
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
            GROUP BY ls.status_name
        ");

        $counts = collect($rows)->pluck('count', 'status_name');

        return [
            'pending'    => (int) ($counts['Pending'] ?? 0),
            'In transit' => (int) ($counts['In transit'] ?? 0),
            'delivered'  => (int) ($counts['Delivered'] ?? 0),
            'returned'   => (int) ($counts['Returned'] ?? 0),
            'cancelled'  => (int) ($counts['Cancelled'] ?? 0),
        ];
    }

    /**
     * Delivery rate stats.
     */
    public function getDeliveryRate($user): array
    {
        $today        = now()->startOfDay()->toDateTimeString();
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $rows = DB::select("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN o.delivery_date >= '{$today}' THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN o.delivery_date < '{$today}' THEN 1 ELSE 0 END) as non_live
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
        ");

        $row = $rows[0];

        return [
            'rate'      => $row->total > 0 ? round(($row->delivered / $row->total) * 100, 2) : 0,
            'total'     => (int) $row->total,
            'delivered' => (int) $row->delivered,
            'live'      => (int) $row->live,
            'non_live'  => (int) $row->non_live,
        ];
    }

    /**
     * Top 5 products by quantity sold (delivered only).
     */
    public function getTopProducts($user)
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        return DB::select("
            SELECT 
                p.product_name as name,
                c.name as category,
                SUM(oi.quantity) as sales
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN products p ON p.id = oi.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            INNER JOIN (
                SELECT ost.order_id
                FROM order_status_timestamps ost
                INNER JOIN (
                    SELECT order_id, MAX(id) as max_id
                    FROM order_status_timestamps
                    GROUP BY order_id
                ) latest ON latest.max_id = ost.id
                INNER JOIN statuses s ON s.id = ost.status_id
                WHERE s.name = 'Delivered'
            ) delivered ON delivered.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
            GROUP BY p.id, p.product_name, c.name
            ORDER BY sales DESC
            LIMIT 5
        ");
    }

    /**
     * Top 5 sellers by deliveries.
     */
    public function getTopSellers($user)
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $rows = DB::select("
            SELECT 
                u.id,
                u.name,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN 1 ELSE 0 END) as delivered
            FROM users u
            INNER JOIN orders o ON o.vendor_id = u.id
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
            GROUP BY u.id, u.name
            HAVING total_orders > 0
            ORDER BY delivered DESC
            LIMIT 5
        ");

        return collect($rows)->map(fn($row) => [
            'id'          => $row->id,
            'name'        => $row->name,
            'deliveries'  => (int) $row->delivered,
            'totalOrders' => (int) $row->total_orders,
            'successRate' => $row->total_orders > 0
                ? round(($row->delivered / $row->total_orders) * 100, 2)
                : 0,
        ]);
    }

    /**
     * Wallet earnings based on delivered orders.
     */
    public function getWalletEarnings($user): array
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $row = DB::select("
            SELECT
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN o.total_price ELSE 0 END) as balance,
                SUM(o.total_price) as target
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
        ")[0];

        $balance  = (float) ($row->balance ?? 0);
        $target   = (float) ($row->target ?? 0);
        $progress = $target > 0 ? round(($balance / $target) * 100, 2) : 0;

        return [
            'balance'  => $balance,
            'target'   => $target,
            'progress' => $progress,
        ];
    }

    /**
     * Inventory stats — scoped to vendor's products and active country.
     */
    public function getInventoryStats($user): array
    {
        $vendorWhere  = $this->isVendor($user) ? "AND p.vendor_id = {$user->id}" : "";
        $countryId    = $this->activeCountryId($user);
        // ✅ Products are scoped by country via the products table directly
        $countryWhere = $countryId ? "AND p.country_id = {$countryId}" : "";

        $row = DB::select("
            SELECT
                COUNT(DISTINCT p.id) as skus,
                COALESCE(SUM(ps.current_stock), 0) as total_units,
                SUM(CASE WHEN ps.current_stock > ps.stock_threshold THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN ps.current_stock > 0 AND ps.current_stock <= ps.stock_threshold THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN ps.current_stock = 0 THEN 1 ELSE 0 END) as out_stock,
                SUM(ps.current_stock * COALESCE(pp.base_price, 0)) as stock_value
            FROM products p
            LEFT JOIN product_stocks ps ON ps.product_id = p.id
            LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.is_active = 1
            WHERE 1=1 {$vendorWhere} {$countryWhere}
        ")[0];

        $restocking = DB::select("
            SELECT
                p.id,
                p.product_name,
                p.sku,
                ps.current_stock,
                ps.stock_threshold,
                ps.committed_stock,
                ps.stock_delivered,
                COALESCE(pp.base_price, 0) as base_price,
                CASE
                    WHEN ps.current_stock = 0 THEN 'out_of_stock'
                    WHEN ps.current_stock <= ps.stock_threshold THEN 'low_stock'
                    ELSE 'ok'
                END as status,
                (ps.stock_threshold - ps.current_stock + 1) as urgency_score
            FROM products p
            LEFT JOIN product_stocks ps ON ps.product_id = p.id
            LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.is_active = 1
            WHERE (ps.current_stock = 0 OR ps.current_stock <= ps.stock_threshold)
                AND ps.stock_threshold > 0
                {$vendorWhere} {$countryWhere}
            ORDER BY urgency_score DESC, ps.current_stock ASC
            LIMIT 10
        ");

        $tracked = $row->in_stock + $row->low_stock + $row->out_stock;

        return [
            'items'      => (int) $row->total_units,
            'skus'       => (int) $row->skus,
            'inStock'    => $tracked > 0 ? round(($row->in_stock / $tracked) * 100, 2) : 0,
            'lowStock'   => $tracked > 0 ? round(($row->low_stock / $tracked) * 100, 2) : 0,
            'outStock'   => $tracked > 0 ? round(($row->out_stock / $tracked) * 100, 2) : 0,
            'stockValue' => round((float) $row->stock_value, 2),
            'restocking' => collect($restocking)->map(fn($r) => [
                'id'            => $r->id,
                'name'          => $r->product_name,
                'sku'           => $r->sku,
                'currentStock'  => (int) $r->current_stock,
                'threshold'     => (int) $r->stock_threshold,
                'committed'     => (int) $r->committed_stock,
                'lastDelivered' => (int) $r->stock_delivered,
                'price'         => (float) $r->base_price,
                'status'        => $r->status,
                'urgencyScore'  => (int) $r->urgency_score,
            ])->toArray(),
        ];
    }

    /**
     * Top agents — delegated to CallAgentPerformanceService.
     */
    public function getTopAgents($user)
    {
        $metricsService = app(MetricsService::class);
        $service        = new CallAgentPerformanceService($metricsService);

        return $service->getTopAgentsPerformance(5);
    }

    /**
     * Order growth: current month vs last month.
     */
    private function calculateOrderGrowth($user): float
    {
        $vendorWhere  = $this->isVendor($user) ? "AND vendor_id = {$user->id}" : "";
        $countryId    = $this->activeCountryId($user);
        // ✅ No alias in this query, so reference orders.country_id directly
        $countryWhere = $countryId ? "AND country_id = {$countryId}" : "";

        $row = DB::select("
            SELECT
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as current_month,
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as last_month
            FROM orders
            WHERE deleted_at IS NULL {$vendorWhere} {$countryWhere}
        ")[0];

        if ($row->last_month == 0) return $row->current_month > 0 ? 100 : 0;

        return round((($row->current_month - $row->last_month) / $row->last_month) * 100, 2);
    }

    /**
     * Average delivery time in hours.
     */
    private function calculateAverageDeliveryTime($user): string
    {
        $vendorWhere  = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";
        $countryWhere = $this->countryWhere($user);  // ✅

        $row = DB::select("
            SELECT AVG(TIMESTAMPDIFF(HOUR, o.created_at, ost.created_at)) as avg_hours
            FROM orders o
            INNER JOIN order_status_timestamps ost ON ost.id = (
                SELECT id FROM order_status_timestamps
                WHERE order_id = o.id
                ORDER BY id DESC LIMIT 1
            )
            INNER JOIN statuses s ON s.id = ost.status_id
            WHERE s.name = 'Delivered'
            AND o.deleted_at IS NULL {$vendorWhere} {$countryWhere}
        ")[0];

        return round((float) ($row->avg_hours ?? 0), 1) . 'h';
    }


    public function getConfirmationSummaryForUser($user): array
    {
        $query = Order::whereNull('deleted_at');

        if ($this->isVendor($user)) {
            $query->where('vendor_id', $user->id);
        }

        if ($user?->country_id) {
            $query->where('country_id', $user->country_id);
        }

        return $this->getConfirmationSummary($query);
    }

    public function getDeliverySummaryForUser($user): array
    {
        $query = Order::whereNull('deleted_at');

        if ($this->isVendor($user)) {
            $query->where('vendor_id', $user->id);
        }

        if ($user?->country_id) {
            $query->where('country_id', $user->country_id);
        }

        return $this->getDeliverySummary($query);
    }


    // orders given per day 



    public function getOrdersGivenSummary($user): array
    {
        $query = Order::query()->whereNull('deleted_at');

        // Vendor scope
        if ($this->isVendor($user)) {
            $query->where('vendor_id', $user->id);
        }

        // Country scope
        if ($user?->country_id) {
            $query->where('country_id', $user->country_id);
        }

        $totalOrders = (clone $query)->count();

        $ordersPerDay = $this->getOrdersGivenByDate(clone $query);

        return [
            'total_orders' => $totalOrders,
            'orders_per_day' => $ordersPerDay,
        ];
    }


    // ✅ Add this private helper once — reuse in every method
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }

        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        return $query;
    }
}
