<?php

namespace App\Services\Order;

use App\Models\Order;

class DuplicateOrderService
{
    public function isDuplicate(array $data): bool
    {
        $customerPhone = $data['customer']['phone'] ?? null;
        if (!$customerPhone) return false;

        $existingOrders = Order::whereHas('customer', function ($q) use ($customerPhone) {
            $q->where('phone', $customerPhone);
        })
        ->whereIn('status_id', [1, 2]) // Pending or In Progress
        ->get();

        foreach ($existingOrders as $order) {
            $existingItems = $order->orderItems->pluck('sku')->sort()->values();
            $newItems = collect($data['order_items'])->pluck('sku')->sort()->values();

            if ($existingItems->toArray() === $newItems->toArray()) {
                return true;
            }
        }

        return false;
    }
}
