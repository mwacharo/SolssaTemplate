<?php

namespace App\Services;

/**
 * MetricsService
 * 
 * A reusable service to calculate key operational metrics for:
 * - Telesales agents
 * - Riders / Delivery personnel
 * - Merchants / Vendors
 * - Zones / Cities
 * 
 * Metrics calculated include:
 * Buyout Rate, Cancellation Rate, Pending Rate, Return Rate, Delivery Rate, Scheduling Rate
 * 
 * Input data should include:
 * - Total orders handled
 * - Delivered / Cancelled / Pending / Returned counts
 * - Out of stock counts
 * - Status breakdown array (scheduled, dispatched, awaiting_dispatch, in_transit, undispatched, rescheduled, returned)
 */
class MetricsService
{
    /**
     * Calculate Buyout Rate
     */
    public function calculateBuyoutRate(int $delivered, int $totalOrders, int $outOfStock): float
    {
        if ($totalOrders - $outOfStock <= 0) return 0;
        return ($delivered / ($totalOrders - $outOfStock)) * 100;
    }

    /**
     * Calculate Cancellation Rate
     */
    public function calculateCancellationRate(int $cancelled, int $totalOrders): float
    {
        if ($totalOrders <= 0) return 0;
        return ($cancelled / $totalOrders) * 100;
    }

    /**
     * Calculate Pending Rate
     */
    public function calculatePendingRate(int $pending, int $totalOrders): float
    {
        if ($totalOrders <= 0) return 0;
        return ($pending / $totalOrders) * 100;
    }

    /**
     * Calculate Return Rate
     */
    public function calculateReturnRate(int $returned, array $statuses): float
    {
        $total = array_sum($statuses);
        if ($total <= 0) return 0;
        return ($returned / $total) * 100;
    }

    /**
     * Calculate Delivery Rate
     */
    public function calculateDeliveryRate(int $delivered, array $statuses): float
    {
        $total = array_sum($statuses);
        if ($total <= 0) return 0;
        return ($delivered / $total) * 100;
    }

    /**
     * Calculate Scheduling Rate
     */
    public function calculateSchedulingRate(array $statuses, int $totalOrders, int $outOfStock): float
    {
        $total = array_sum($statuses);
        if ($totalOrders - $outOfStock <= 0) return 0;
        return ($total / ($totalOrders - $outOfStock)) * 100;
    }

    /**
     * Generate metrics for any entity
     * 
     * @param string $entityType - Type of entity (agent, rider, vendor, zone, city)
     * @param array $data - Array containing data:
     * [
     *   'totalOrders' => int,
     *   'outOfStock' => int,
     *   'delivered' => int,
     *   'cancelled' => int,
     *   'pending' => int,
     *   'returned' => int,
     *   'statuses' => array (scheduled, dispatched, awaiting_dispatch, in_transit, undispatched, rescheduled, returned)
     * ]
     * 
     * @return array - Metrics percentages
     */
    public function generateMetrics(string $entityType, array $data): array
    {
        return [
            'EntityType' => $entityType,
            'BuyoutRate' => $this->calculateBuyoutRate($data['delivered'], $data['totalOrders'], $data['outOfStock']),
            'CancellationRate' => $this->calculateCancellationRate($data['cancelled'], $data['totalOrders']),
            'PendingRate' => $this->calculatePendingRate($data['pending'], $data['totalOrders']),
            'ReturnRate' => $this->calculateReturnRate($data['returned'], $data['statuses']),
            'DeliveryRate' => $this->calculateDeliveryRate($data['delivered'], $data['statuses']),
            'SchedulingRate' => $this->calculateSchedulingRate($data['statuses'], $data['totalOrders'], $data['outOfStock']),
        ];
    }

    /**
     * Generate bulk metrics for multiple entities
     * 
     * @param string $entityType - Type of entity (agent, rider, vendor, zone, city)
     * @param array $entitiesData - Array of multiple entity data arrays
     * 
     * @return array - Array of metrics per entity
     */
    public function generateBulkMetrics(string $entityType, array $entitiesData): array
    {
        $result = [];
        foreach ($entitiesData as $key => $data) {
            $metrics = $this->generateMetrics($entityType, $data);
            $metrics['EntityName'] = $data['name'] ?? "Entity {$key}";
            $result[] = $metrics;
        }
        return $result;
    }
}
