<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Reusable vendor scope — returns base orders query scoped to vendor if applicable.
     */
    private function ordersQuery($user)
    {
        $query = DB::table('orders')->whereNull('orders.deleted_at');

        if ($this->isVendor($user)) {
            $query->where('orders.vendor_id', $user->id);
        }

        return $query;
    }

    private function isVendor($user): bool
    {
        return in_array($user?->getRoleNames()->first(), ['vendor', 'Vendor']);
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
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        $rows = DB::select("
            SELECT 
                ls.status_name,
                COUNT(*) as count
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere}
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
        $now         = Carbon::now();
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        switch ($period) {
            case '1M':
                $groupExpr = "WEEK(o.created_at)";
                $labels    = collect(range(3, 0))->map(fn($w) => $now->copy()->subWeeks($w)->format('W'));
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
            {$vendorWhere}
            GROUP BY ls.status_name, {$groupExpr}
        ");

        // Build result keyed by status → period → count
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
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        $rows = DB::select("
            SELECT ls.status_name, COUNT(*) as count
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere}
            GROUP BY ls.status_name
        ");

        $counts = collect($rows)->pluck('count', 'status_name');

        return [
            'pending'   => (int) ($counts['Pending'] ?? 0),
            'shipped'   => (int) ($counts['Shipped'] ?? 0),
            'delivered' => (int) ($counts['Delivered'] ?? 0),
            'returned'  => (int) ($counts['Returned'] ?? 0),
            'cancelled' => (int) ($counts['Cancelled'] ?? 0),
        ];
    }

    /**
     * Delivery rate stats.
     */
    public function getDeliveryRate($user): array
    {
        $today       = now()->startOfDay()->toDateTimeString();
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        $rows = DB::select("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN o.delivery_date >= '{$today}' THEN 1 ELSE 0 END) as live,
                SUM(CASE WHEN o.delivery_date < '{$today}' THEN 1 ELSE 0 END) as non_live
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere}
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
     * Top 5 products by quantity sold.
     */
    public function getTopProducts($user)
    {
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        return DB::select("
            SELECT 
                p.product_name as name,
                c.name as category,
                SUM(oi.quantity) as sales
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN products p ON p.id = oi.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE o.deleted_at IS NULL {$vendorWhere}
            GROUP BY p.product_name, c.name
            ORDER BY sales DESC
            LIMIT 5
        ");
    }

    /**
     * Top 5 sellers by deliveries.
     */
    public function getTopSellers($user)
    {
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        $rows = DB::select("
            SELECT 
                u.id,
                u.name,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN 1 ELSE 0 END) as delivered
            FROM users u
            INNER JOIN orders o ON o.vendor_id = u.id
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere}
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
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

        $row = DB::select("
            SELECT
                SUM(CASE WHEN ls.status_name = 'Delivered' THEN o.total_price ELSE 0 END) as balance,
                SUM(o.total_price) as target
            FROM orders o
            LEFT JOIN ({$this->latestStatusSubquery()}) ls ON ls.order_id = o.id
            WHERE o.deleted_at IS NULL {$vendorWhere}
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
     * Inventory stats — scoped to vendor's products if applicable.
     */
    public function getInventoryStats($user): array
    {
        $vendorWhere = $this->isVendor($user) ? "AND p.vendor_id = {$user->id}" : "";

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
            WHERE 1=1 {$vendorWhere}
        ")[0];

        $tracked = $row->in_stock + $row->low_stock + $row->out_stock;

        return [
            'items'      => (int) $row->total_units,
            'skus'       => (int) $row->skus,
            'inStock'    => $tracked > 0 ? round(($row->in_stock / $tracked) * 100, 2) : 0,
            'lowStock'   => $tracked > 0 ? round(($row->low_stock / $tracked) * 100, 2) : 0,
            'outStock'   => $tracked > 0 ? round(($row->out_stock / $tracked) * 100, 2) : 0,
            'stockValue' => round((float) $row->stock_value, 2),
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
        $vendorWhere = $this->isVendor($user) ? "AND vendor_id = {$user->id}" : "";

        $row = DB::select("
            SELECT
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as current_month,
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(NOW() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as last_month
            FROM orders
            WHERE deleted_at IS NULL {$vendorWhere}
        ")[0];

        if ($row->last_month == 0) return $row->current_month > 0 ? 100 : 0;

        return round((($row->current_month - $row->last_month) / $row->last_month) * 100, 2);
    }

    /**
     * Average delivery time in hours.
     */
    private function calculateAverageDeliveryTime($user): string
    {
        $vendorWhere = $this->isVendor($user) ? "AND o.vendor_id = {$user->id}" : "";

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
            AND o.deleted_at IS NULL {$vendorWhere}
        ")[0];

        return round((float) ($row->avg_hours ?? 0), 1) . 'h';
    }
}
