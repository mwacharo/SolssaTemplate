<?php

namespace App\Services;

use App\Models\User;
use App\Services\MetricsService;

class CallAgentPerformanceService
{
    protected MetricsService $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Get top call agents by number of orders handled
     */
    public function getTopAgents(int $limit = 5)
    {
        return User::role(['CallAgent'])
            ->withCount([
                'orders as total_orders' => function ($query) {
                    $query->whereNull('deleted_at');
                }
            ])
            ->orderByDesc('total_orders')
            ->take($limit)
            ->get();
    }

    /**
     * Calculate metrics for each agent
     */
    public function calculateAgentMetrics($agents): array
    {
        $agentMetrics = [];

        foreach ($agents as $agent) {

            $totalOrders = $agent->orders()->count();

            // Load latest status
            $orders = $agent->orders()->with('latestStatus.status')->get();

            /**
             * Normalize DB statuses into snake_case, lowercase keys
             * so "Delivered" → "delivered"
             */
            $statusCounts = [
                'new' => 0,
                'pending' => 0,
                'scheduled' => 0,
                'shipped' => 0,
                'delivered' => 0,
                'cancelled' => 0,
                'returned' => 0,
            ];

            foreach ($orders as $order) {

                $statusName = optional($order->latestStatus->status)->name ?? null;

                if ($statusName) {
                    // Normalize DB status → snake-case lowercase
                    $key = str()->snake(strtolower($statusName));

                    // Only count keys you have defined
                    if (array_key_exists($key, $statusCounts)) {
                        $statusCounts[$key]++;
                    }
                }
            }

            // Call metrics
            $totalCalls = $agent->callHistories()->count();
            $avgCallTime = $agent->callHistories()->avg('durationInSeconds');
            $rating = $agent->transcripts()->avg('cs_rating') ?? 0;

            // Calculate performance score via MetricsService
            $metrics = $this->metricsService->generateMetrics('CallAgent', [
                'totalOrders' => $totalOrders,
                'outOfStock' => 0, // not used
                'delivered' => $statusCounts['delivered'],
                'cancelled' => $statusCounts['cancelled'],
                'pending' => $statusCounts['pending'],
                'returned' => $statusCounts['returned'],

                'statuses' => $statusCounts,
            ]);

            // Final output
            $agentMetrics[] = array_merge($metrics, [
                'name' => $agent->name,
                'totalCalls' => $totalCalls,
                'avgCallTime' => round($avgCallTime, 1),
                'rating' => round($rating, 1),
                'shift' => $agent->shift ?? 'N/A',
                'experience' => $agent->experience ?? 'N/A',
                'confirmationTrend' => 0,
                'deliveryTrend' => 0,
            ]);
        }

        return $agentMetrics;
    }

    /**
     * Get formatted metrics for dashboard
     */
    public function getTopAgentsPerformance(int $limit = 5): array
    {
        $agents = $this->getTopAgents($limit);
        return $this->calculateAgentMetrics($agents);
    }
}
