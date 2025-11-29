<?php

namespace App\Services;

use App\Models\Order;
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

    /**
     * Top 5 products by order frequency.
     */
    public function getTopProducts()
    {
        return DB::table('order_items')
            ->select('product_name', DB::raw('COUNT(*) as total'))
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();
    }

    /**
     * Top 5 vendors by order count.
     */
    public function getTopSellers()
    {
        return DB::table('orders')
            ->join('users', 'orders.vendor_id', '=', 'users.id')
            ->select('users.name', DB::raw('COUNT(orders.id) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();
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
}
