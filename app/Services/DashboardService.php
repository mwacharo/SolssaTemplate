<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getOrderStats(): array 
    {
        \Log::debug('Fetching latest order statuses');
        $latestStatuses = Order::with([
            'statusTimestamps' => fn($q) => $q->latest('created_at')->limit(1),
            'statusTimestamps.status'
        ])
        ->get()
        ->map(function ($order) {
            $latestStatusTimestamp = $order->statusTimestamps->first();
            $statusName = $latestStatusTimestamp?->status?->name;
            \Log::debug('Order ID: ' . $order->id . ', Latest Status: ' . ($statusName ?? 'none'));
            return $statusName;
        })
        ->filter();

        \Log::debug('Latest statuses collection', ['statuses' => $latestStatuses->values()->all()]);
        $statusCounts = $latestStatuses->countBy();
        \Log::debug('Status counts', $statusCounts->toArray());

        return [
            'totalOrders' => $latestStatuses->count(),
            'deliveries'  => $statusCounts['delivered'] ?? 0,
            'cancelled'   => $statusCounts['cancelled'] ?? 0,
            'pending'     => $statusCounts['pending'] ?? 0,
            'scheduled'   => $statusCounts['Scheduled'] ?? 0,
    ];
}

public function getOrderAnalytics(): array
    {
        // Example: group orders by month
        $confirmed = Order::select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total'))
            ->whereHas('statusTimestamps', function ($query) {
                $query->latest('created_at')->limit(1)
                      ->whereHas('status', function ($q) {
                          $q->where('name', 'Scheduled');
                      });
            })
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        $delivered = Order::select(DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total'))
            ->where('delivery_status', 'delivered')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        return [
            ['name' => 'Confirmed', 'data' => $confirmed],
            ['name' => 'Delivered', 'data' => $delivered],
        ];
    }

    public function getInventoryStats(): array
    {
        return [
            'items'    => 120,
            'skus'     => 35,
            'inStock'  => 95,
            'lowStock' => 10,
            'outStock' => 5,
        ];
    }

    public function getStatusOverview(): array
    {
        return [
            'pending'   => Order::where('status', 'pending')->count(),
            'shipped'   => Order::where('status', 'shipped')->count(),
            'delivered' => Order::where('delivery_status', 'delivered')->count(),
            'returned'  => Order::where('status', 'returned')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
        ];
    }

    public function getTopAgents()
    {
        return User::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();
    }

    public function getDeliveryRate(): array
    {
        $total = Order::count();
        $delivered = Order::where('delivery_status', 'delivered')->count();

        return [
            'rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
        ];
    }

    public function getTopProducts()
    {
        return DB::table('order_items')
            ->select('product_name', DB::raw('count(*) as total'))
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();
    }

    public function getTopSellers()
    {
        return DB::table('orders')
            ->join('users', 'orders.vendor_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(orders.id) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();
    }

    public function getWalletEarnings(): array
    {
        return [
            'balance'  => 12500,
            'progress' => 75,
        ];
    }
}
