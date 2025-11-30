<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusTimestamp;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    /**
     * Get high-level counts of orders by latest status.
     */
    public function getOrderStats(): array
    {
        // Get the latest status for each order
        $statuses = Order::with('latestStatus.status')
            ->get()
            ->map(fn($order) => $order->latestStatus?->status?->name)
            ->filter();

        $statusCounts = $statuses->countBy();

        $totalOrders = $statuses->count();
        $delivered   = $statusCounts->get('Delivered', 0);
        $pending     = $statusCounts->get('Pending', 0);

        // Delivery rate (success percentage)
        $deliveryRate = $totalOrders > 0
            ? round(($delivered / $totalOrders) * 100, 2)
            : 0;

        // Order growth (mock: +12% — you can compute by comparing last month vs this month)
        $orderGrowth = 12;

        // Monthly target (example: at least 150, or +20 from current)
        $monthlyTarget = max($totalOrders + 20, 150);

        // Progress towards target
        $orderProgress = $monthlyTarget > 0
            ? min(100, round(($totalOrders / $monthlyTarget) * 100, 2))
            : 0;

        // Average delivery time (mocked for now)
        $averageDeliveryTime = "2.4h";

        return [
            'totalOrders'        => $totalOrders,
            'deliveries'         => $delivered,
            'pending'            => $pending,
            'deliveryRate'       => $deliveryRate,
            'orderGrowth'        => $orderGrowth,
            'monthlyTarget'      => $monthlyTarget,
            'orderProgress'      => $orderProgress,
            'averageDeliveryTime' => $averageDeliveryTime,
            'statusCounts'       => $statusCounts, // full dynamic breakdown
        ];
    }


    /**
     * Get analytics (confirmed vs delivered grouped by month).
     */
    public function getOrderAnalytics(string $period = '6M')
    {
        $orders = Order::with('latestStatus.status')->get();

        // Get all unique statuses dynamically
        $statuses = $orders
            ->map(fn($o) => strtolower($o->latestStatus?->status?->name))
            ->filter()
            ->unique()
            ->values();

        $now = Carbon::now();

        switch ($period) {
            case '1M': // Last 4 weeks
                $labels = collect(range(0, 3))
                    ->map(fn($w) => $now->copy()->subWeeks($w)->format('W'))
                    ->reverse()
                    ->values();
                $groupBy = fn($o) => $o->created_at->format('W');
                break;

            case '3M': // Last 3 months
                $labels = collect(range(0, 2))
                    ->map(fn($m) => $now->copy()->subMonths($m)->format('M'))
                    ->reverse()
                    ->values();
                $groupBy = fn($o) => $o->created_at->format('M');
                break;

            case '1Y': // 4 quarters
                $labels = collect([1, 2, 3, 4]);
                $groupBy = fn($o) => ceil($o->created_at->month / 3);
                $year = $now->year;
                break;

            case '6M':
            default: // Last 6 months
                $labels = collect(range(0, 5))
                    ->map(fn($m) => $now->copy()->subMonths($m)->format('M'))
                    ->reverse()
                    ->values();
                $groupBy = fn($o) => $o->created_at->format('M');
                break;
        }

        $result = [];

        foreach ($statuses as $status) {
            $filtered = $orders->filter(
                fn($o) =>
                strtolower($o->latestStatus?->status?->name) === $status
                    && (!isset($year) || $o->created_at->year == $year)
            );

            $data = $labels->map(
                fn($label) =>
                $filtered->filter(fn($o) => $groupBy($o) == $label)->count()
            )->toArray();

            $result[] = [
                'name' => ucfirst($status),
                'data' => $data,
            ];
        }

        return $result;
    }

    /**
     * Mock inventory stats (replace with real warehouse/product queries).
     */
    public function getInventoryStats(): array
    {
        // distinct products (SKUs)
        $skus = Product::count();

        // total stock units across all products
        $items = DB::table('product_stocks')->sum('current_stock');

        // counts by condition (using stock_threshold from product_stocks)
        $inStockCount = DB::table('product_stocks')
            ->whereColumn('current_stock', '>', 'stock_threshold')
            ->count();

        $lowStockCount = DB::table('product_stocks')
            ->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'stock_threshold')
            ->count();

        $outStockCount = DB::table('product_stocks')
            ->where('current_stock', '=', 0)
            ->count();

        $totalTracked = $inStockCount + $lowStockCount + $outStockCount;

        // avoid division by zero
        $inStockPercent  = $totalTracked > 0 ? round(($inStockCount / $totalTracked) * 100, 2) : 0;
        $lowStockPercent = $totalTracked > 0 ? round(($lowStockCount / $totalTracked) * 100, 2) : 0;
        $outStockPercent = $totalTracked > 0 ? round(($outStockCount / $totalTracked) * 100, 2) : 0;

        // Optional: calculate stock value (if product_stocks has `price`)
        // Calculate stock value using the latest active price from product_prices
        // Calculate stock value using Eloquent relationships for accuracy
        $stockValue = Product::with(['prices' => function ($q) {
            $q->where('is_active', true);
        }, 'stocks'])
            ->get()
            ->sum(function ($product) {
                $activePrice = $product->prices->first()?->base_price ?? 0;
                $totalStock = $product->stocks->sum('current_stock');
                return $activePrice * $totalStock;
            });

        return [
            'items'     => $items,       // total units
            'skus'      => $skus,        // distinct products
            'inStock'   => $inStockPercent,
            'lowStock'  => $lowStockPercent,
            'outStock'  => $outStockPercent,
            'stockValue' => round($stockValue, 2), // optional
        ];
    }


    /**
     * Status overview (by latest status, not raw columns).
     */
    public function getStatusOverview(): array
    {
        $statuses = Order::with('latestStatus.status')
            ->get()
            ->map(fn($o) => $o->latestStatus?->status?->name)
            ->filter()
            ->countBy();

        return [
            'pending'   => $statuses['Pending'] ?? 0,
            'shipped'   => $statuses['Shipped'] ?? 0,
            'delivered' => $statuses['Delivered'] ?? 0,
            'returned'  => $statuses['Returned'] ?? 0,
            'cancelled' => $statuses['Cancelled'] ?? 0,
            // 'new'     => $statuses['New'] ?? 0,
            // 'scheduled' => $statuses['Scheduled'] ?? 0,
            ''
        ];
    }

    /**
     * Top 5 agents by number of orders handled.
     */
    public function getTopAgents()
    {
        return User::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();
    }

    /**
     * Delivery success rate (%).
     */
    public function getDeliveryRate(): array
    {
        $total = Order::count();

        $delivered = Order::with('latestStatus.status')
            ->get()
            ->filter(fn($o) => $o->latestStatus?->status?->name === 'delivered')
            ->count();

        return [
            'rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
        ];
    }

    // public function getTopProducts()
    // {
    //     return DB::table('order_items')
    //         ->join('products', 'order_items.product_id', '=', 'products.id')
    //         ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    //         ->select(
    //             'products.product_name as name',
    //             DB::raw('SUM(order_items.quantity) as sales'),
    //             'categories.name as category'
    //         )
    //         ->groupBy('products.product_name', 'categories.name')
    //         ->orderByDesc('sales')
    //         ->take(5)
    //         ->get();
    // }



    public function getTopProducts()
    {
        return Product::query()
            ->with('category')
            ->select(
                'products.id',
                'products.product_name as name',
                'categories.name as category',
                DB::raw('SUM(order_items.quantity) as total_sales')
            )
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')

            // Ensure the order belongs to the same vendor
            ->whereColumn('orders.vendor_id', 'products.vendor_id')

            // In case soft deletes exist in orders
            ->whereNull('orders.deleted_at')

            // In case soft deletes exist in products
            ->whereNull('products.deleted_at')

            ->groupBy('products.id', 'products.product_name', 'categories.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();
    }







    public function getDashboardData()
    {
        $vendorId = auth()->id(); // or however you get the current vendor ID

        // Get all orders for the vendor
        $orders = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->with('latestStatus.status')
            ->get();

        // Total orders
        $totalOrders = $orders->count();

        // Count deliveries (orders with latest status = "Delivered")
        $deliveries = $orders->filter(function ($order) {
            return $order->latestStatus
                && $order->latestStatus->status
                && $order->latestStatus->status->name === 'Delivered';
        })->count();

        // Delivery rate
        $deliveryRate = $totalOrders > 0 ? round(($deliveries / $totalOrders) * 100, 2) : 0;

        // Count orders by status
        $statusCounts = [];
        foreach ($orders as $order) {
            if ($order->latestStatus && $order->latestStatus->status) {
                $statusName = $order->latestStatus->status->name;
                if (!isset($statusCounts[$statusName])) {
                    $statusCounts[$statusName] = 0;
                }
                $statusCounts[$statusName]++;
            }
        }

        // Count pending (assuming "New" or "Scheduled" = pending)
        $pending = ($statusCounts['New'] ?? 0) + ($statusCounts['Scheduled'] ?? 0);

        // Order chart data - last 6 periods (days/weeks/months)
        $orderChart = $this->getOrderChartData($vendorId);

        // Status data for pie chart
        $statusData = [
            'pending' => $statusCounts['New'] ?? 0,
            'shipped' => $statusCounts['Scheduled'] ?? 0, // or whatever your "shipped" status is
            'delivered' => $statusCounts['Delivered'] ?? 0,
            'returned' => $statusCounts['Returned'] ?? 0,
            'cancelled' => $statusCounts['Cancelled'] ?? 0,
        ];

        return [
            'orderStats' => [
                'totalOrders' => $totalOrders,
                'deliveries' => $deliveries,
                'pending' => $pending,
                'deliveryRate' => $deliveryRate,
                'orderGrowth' => $this->calculateOrderGrowth($vendorId),
                'monthlyTarget' => 150, // This should come from settings
                'orderProgress' => $totalOrders, // or calculate based on target
                'averageDeliveryTime' => $this->calculateAverageDeliveryTime($vendorId),
                'statusCounts' => $statusCounts,
            ],
            'orderChart' => $orderChart,
            'inventory' => $this->getInventoryStats($vendorId),
            'statusData' => $statusData,
            'topProducts' => $this->getTopProducts($vendorId),
            'topSellers' => $this->getTopSellers(),
            'wallet' => $this->getWalletData($vendorId),
        ];
    }

    // Get order chart data for the last 6 periods
    private function getOrderChartData($vendorId)
    {
        // Get last 6 days of orders grouped by status
        $startDate = now()->subDays(6)->startOfDay();

        $orders = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $startDate)
            ->with('latestStatus.status')
            ->get()
            ->groupBy(function ($order) {
                return $order->created_at->format('Y-m-d');
            });

        // Initialize data structure
        $chartData = [];
        $dates = [];

        // Get last 6 days
        for ($i = 5; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }

        // Get all unique statuses
        $allStatuses = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->with('latestStatus.status')
            ->get()
            ->pluck('latestStatus.status.name')
            ->filter()
            ->unique()
            ->values();

        // Initialize chart data for each status
        foreach ($allStatuses as $status) {
            $chartData[$status] = array_fill(0, 6, 0);
        }

        // Fill in the actual counts
        foreach ($dates as $index => $date) {
            if (isset($orders[$date])) {
                foreach ($orders[$date] as $order) {
                    if ($order->latestStatus && $order->latestStatus->status) {
                        $statusName = $order->latestStatus->status->name;
                        if (isset($chartData[$statusName])) {
                            $chartData[$statusName][$index]++;
                        }
                    }
                }
            }
        }

        // Convert to required format
        $result = [];
        foreach ($chartData as $statusName => $data) {
            $result[] = [
                'name' => $statusName,
                'data' => $data,
            ];
        }

        return $result;
    }

    // Calculate order growth percentage
    private function calculateOrderGrowth($vendorId)
    {
        $currentMonth = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        if ($lastMonth == 0) {
            return $currentMonth > 0 ? 100 : 0;
        }

        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    // Calculate average delivery time
    private function calculateAverageDeliveryTime($vendorId)
    {
        $deliveredOrders = Order::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereHas('latestStatus.status', function ($query) {
                $query->where('name', 'Delivered');
            })
            ->with('latestStatus')
            ->get();

        if ($deliveredOrders->isEmpty()) {
            return '0h';
        }

        $totalHours = 0;
        foreach ($deliveredOrders as $order) {
            $createdAt = $order->created_at;
            $deliveredAt = $order->latestStatus->created_at;
            $hours = $createdAt->diffInHours($deliveredAt);
            $totalHours += $hours;
        }

        $averageHours = $totalHours / $deliveredOrders->count();

        return round($averageHours, 1) . 'h';
    }





    // / Alternative using Eloquent relationships (recommended)
    public function getTopSellersX()
    {
        $sellers = User::withCount([
            'orders as total_orders' => function ($query) {
                $query->whereNull('deleted_at');
            }
        ])
            ->with(['orders' => function ($query) {
                $query->whereNull('deleted_at')
                    ->with('latestStatus.status');
            }])
            ->having('total_orders', '>', 0)
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();

        return $sellers->map(function ($seller) {
            $orders = $seller->orders;
            $totalOrders = $orders->count();

            $deliveredOrders = $orders->filter(function ($order) {
                return $order->latestStatus
                    && $order->latestStatus->status
                    && $order->latestStatus->status->name === 'Delivered';
            })->count();

            $successRate = $totalOrders > 0
                ? round(($deliveredOrders / $totalOrders) * 100, 2)
                : 0.00;

            return [
                'id' => $seller->id,
                'name' => $seller->name,
                'deliveries' => $totalOrders,
                'successRate' => $successRate,
            ];
        });
    }



    /**
     * Wallet earnings (mocked).
     */
    // public function getWalletEarnings(): array
    // {
    //     return [
    //         'balance'  => 12500,
    //         'progress' => 75,
    //     ];
    // }


    public function getWalletEarnings(): array
    {



        $balance = Order::with('latestStatus.status')
            ->get()
            ->filter(fn($o) => $o->latestStatus?->status?->name === 'Delivered')
            ->sum('total_price');

        // Sum of ALL orders (target)
        $target = Order::sum('total_price');

        // Avoid division by zero
        $progress = $target > 0
            ? round(($balance / $target) * 100, 2)
            : 0;

        return [
            'balance'  => (float) $balance,
            'target'   => (float) $target,
            'progress' => $progress,
        ];
    }



    // RECOMMENDED SOLUTION - Simple and Reliable
    // public function getTopSellers()
    // {
    //     $sellers = User::whereHas('orders', function ($query) {
    //         $query->whereNull('deleted_at');
    //     })
    //         ->get();

    //     $results = [];

    //     foreach ($sellers as $seller) {
    //         // Get all non-deleted orders for this seller
    //         $orders = Order::where('vendor_id', $seller->id)
    //             ->whereNull('deleted_at')
    //             ->get();

    //         $totalOrders = $orders->count();

    //         if ($totalOrders === 0) {
    //             continue; // Skip sellers with no orders
    //         }

    //         $deliveredCount = 0;

    //         // Check each order's latest status
    //         foreach ($orders as $order) {
    //             // Get the latest status timestamp
    //             $latestStatus = OrderStatusTimestamp::where('order_id', $order->id)
    //                 ->with('status')
    //                 ->orderBy('created_at', 'DESC')
    //                 ->first();

    //             // Check if the latest status is "Delivered"
    //             if ($latestStatus && $latestStatus->status && $latestStatus->status->name === 'Delivered') {
    //                 $deliveredCount++;
    //             }
    //         }

    //         $successRate = round(($deliveredCount / $totalOrders) * 100, 2);

    //         $results[] = [
    //             'id' => $seller->id,
    //             'name' => $seller->name,
    //             'deliveries' => $totalOrders,
    //             'successRate' => $successRate,
    //         ];
    //     }

    //     // Sort by deliveries (total orders) descending
    //     usort($results, function ($a, $b) {
    //         return $b['deliveries'] <=> $a['deliveries'];
    //     });

    //     // Return top 5
    //     return array_slice($results, 0, 5);
    // }



    public function getTopSellers()
    {
        // Load sellers with their orders and each order's latest status → super efficient
        $sellers = User::whereHas('orders', function ($q) {
            $q->whereNull('deleted_at');
        })
            ->with([
                'orders' => function ($q) {
                    $q->whereNull('deleted_at')
                        ->with(['latestStatus.status']); // eager load latest status
                }
            ])
            ->get();

        $results = [];

        foreach ($sellers as $seller) {

            $orders = $seller->orders;
            $totalOrders = $orders->count();

            if ($totalOrders === 0) {
                continue;
            }

            // Count only orders whose latest status is "Delivered"
            $deliveredCount = $orders->filter(function ($order) {
                return optional($order->latestStatus?->status)->name === 'Delivered';
            })->count();

            $successRate = $totalOrders > 0
                ? round(($deliveredCount / $totalOrders) * 100, 2)
                : 0;

            $results[] = [
                'id'          => $seller->id,
                'name'        => $seller->name,
                'deliveries'  => $deliveredCount,
                'totalOrders' => $totalOrders,
                'successRate' => $successRate,
            ];
        }

        // Sort by number of delivered orders
        usort($results, function ($a, $b) {
            return $b['deliveries'] <=> $a['deliveries'];
        });

        return array_slice($results, 0, 5);
    }


    // ALTERNATIVE: Optimized with fewer queries
    public function getTopSellersOptimized()
    {
        // Get all latest status timestamps in one query
        $latestStatuses = OrderStatusTimestamp::select('order_status_timestamps.*')
            ->join(
                DB::raw('(SELECT order_id, MAX(created_at) as max_created_at 
                      FROM order_status_timestamps 
                      GROUP BY order_id) as latest'),
                function ($join) {
                    $join->on('order_status_timestamps.order_id', '=', 'latest.order_id')
                        ->on('order_status_timestamps.created_at', '=', 'latest.max_created_at');
                }
            )
            ->with('status')
            ->get()
            ->keyBy('order_id');

        // Get all sellers with their orders
        $sellers = User::whereHas('orders', function ($query) {
            $query->whereNull('deleted_at');
        })
            ->with(['orders' => function ($query) {
                $query->whereNull('deleted_at')
                    ->select('id', 'vendor_id');
            }])
            ->get();

        $results = [];

        foreach ($sellers as $seller) {
            $totalOrders = $seller->orders->count();

            if ($totalOrders === 0) continue;

            $deliveredCount = 0;

            foreach ($seller->orders as $order) {
                $latestStatus = $latestStatuses->get($order->id);

                if (
                    $latestStatus &&
                    $latestStatus->status &&
                    $latestStatus->status->name === 'Delivered'
                ) {
                    $deliveredCount++;
                }
            }

            $successRate = round(($deliveredCount / $totalOrders) * 100, 2);

            $results[] = [
                'id' => $seller->id,
                'name' => $seller->name,
                'deliveries' => $totalOrders,
                'successRate' => $successRate,
            ];
        }

        usort($results, fn($a, $b) => $b['deliveries'] <=> $a['deliveries']);

        return array_slice($results, 0, 5);
    }

    // PURE SQL APPROACH - Most Efficient
    public function getTopSellersSql()
    {
        $results = DB::select("
        SELECT 
            u.id,
            u.name,
            COUNT(DISTINCT o.id) as deliveries,
            ROUND(
                COUNT(DISTINCT CASE 
                    WHEN s.name = 'Delivered' THEN o.id 
                END) * 100.0 / COUNT(DISTINCT o.id), 
                2
            ) as successRate
        FROM users u
        INNER JOIN orders o ON o.vendor_id = u.id AND o.deleted_at IS NULL
        LEFT JOIN (
            SELECT 
                ost1.order_id,
                ost1.status_id
            FROM order_status_timestamps ost1
            WHERE ost1.created_at = (
                SELECT MAX(ost2.created_at)
                FROM order_status_timestamps ost2
                WHERE ost2.order_id = ost1.order_id
            )
        ) as latest_status ON latest_status.order_id = o.id
        LEFT JOIN statuses s ON s.id = latest_status.status_id
        GROUP BY u.id, u.name
        HAVING deliveries > 0
        ORDER BY deliveries DESC
        LIMIT 5
    ");

        return collect($results)->map(fn($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'deliveries' => (int) $row->deliveries,
            'successRate' => (float) $row->successRate,
        ]);
    }

    // DEBUG FUNCTION - Use this to see what's happening
    public function debugOrderStatuses()
    {
        $orders = Order::where('vendor_id', 3)
            ->whereNull('deleted_at')
            ->get();

        echo "=== DEBUG: Order Statuses ===\n\n";
        echo "Total Orders: " . $orders->count() . "\n\n";

        foreach ($orders as $order) {
            echo "Order #{$order->id} (No: {$order->order_no})\n";

            // Get all statuses for this order
            $statuses = OrderStatusTimestamp::where('order_id', $order->id)
                ->with('status')
                ->orderBy('created_at', 'DESC')
                ->get();

            echo "  Status History:\n";
            foreach ($statuses as $st) {
                $statusName = $st->status ? $st->status->name : 'NULL';
                echo "    - {$statusName} at {$st->created_at}\n";
            }

            // Get latest
            $latest = $statuses->first();
            $latestName = $latest && $latest->status ? $latest->status->name : 'NULL';
            echo "  Latest Status: {$latestName}\n";
            echo "  Is Delivered? " . ($latestName === 'Delivered' ? 'YES' : 'NO') . "\n";
            echo "\n";
        }

        // Summary
        $deliveredCount = 0;
        foreach ($orders as $order) {
            $latest = OrderStatusTimestamp::where('order_id', $order->id)
                ->with('status')
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($latest && $latest->status && $latest->status->name === 'Delivered') {
                $deliveredCount++;
            }
        }

        echo "=== SUMMARY ===\n";
        echo "Total Orders: {$orders->count()}\n";
        echo "Delivered Orders: {$deliveredCount}\n";
        echo "Success Rate: " . round(($deliveredCount / $orders->count()) * 100, 2) . "%\n";
    }

    // TEST FUNCTION - Quick check
    public function testTopSellers()
    {
        echo "Testing getTopSellers():\n";
        $sellers = $this->getTopSellers();

        foreach ($sellers as $seller) {
            echo "\nSeller: {$seller['name']}\n";
            echo "  Total Orders: {$seller['deliveries']}\n";
            echo "  Success Rate: {$seller['successRate']}%\n";
        }
    }
}
