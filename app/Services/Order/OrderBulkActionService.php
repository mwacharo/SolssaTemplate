<?php



namespace App\Services\Order;

use App\Models\Order;



class OrderBulkActionService
{
    public function assignRider(array $orderIds, int $riderId): void
    {
        Order::whereIn('id', $orderIds)->update(['rider_id' => $riderId]);
    }

    public function assignAgent(array $orderIds, int $agentId): void
    {
        Order::whereIn('id', $orderIds)->update(['agent_id' => $agentId]);
    }

    public function updateStatus(array $orderIds, string $status): void
    {
        Order::whereIn('id', $orderIds)->update(['status' => $status]);
    }
}
// This service class provides methods to perform bulk actions on orders such as assigning a rider, assigning an agent, and updating the status of multiple orders.