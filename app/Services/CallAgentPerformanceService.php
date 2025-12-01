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
                'assignedOrders as total_orders'
            ])
            ->orderByDesc('total_orders')
            ->take($limit)
            ->get();
    }

    /**
     * Calculate metrics for given agents
     */
    public function calculateAgentMetrics($agents): array
    {
        \Log::info('Starting agent metrics calculation', ['agent_count' => count($agents)]);

        $agentMetrics = [];

        foreach ($agents as $agent) {
            // Load orders with their latest status using the relationship
            $orders = $agent->assignedOrders()
                ->with('latestStatus.status')
                ->get();

            $totalOrders = $orders->count();

            // Initialize status counts with proper capitalization
            $statusCounts = [
                'New' => 0,
                'Pending' => 0,
                'Scheduled' => 0,
                'Shipped' => 0,
                'Delivered' => 0,
                'Cancelled' => 0,
                'Returned' => 0,
            ];

            // Count statuses
            foreach ($orders as $order) {
                $statusName = optional($order->latestStatus->status)->name ?? null;

                if ($statusName) {
                    // Normalize status name to handle case variations
                    $key = ucfirst(strtolower($statusName));

                    if (array_key_exists($key, $statusCounts)) {
                        $statusCounts[$key]++;
                    } else {
                        \Log::warning('Unknown status encountered', [
                            'agent_id' => $agent->id,
                            'order_id' => $order->id,
                            'status' => $statusName,
                            'normalized' => $key
                        ]);
                    }
                } else {
                    \Log::warning('Order without status', [
                        'agent_id' => $agent->id,
                        'order_id' => $order->id
                    ]);
                }
            }

            // Call metrics
            $totalCalls = $agent->callHistories()->count();
            $avgCallTime = $agent->callHistories()->avg('durationInSeconds') ?? 0;
            $rating = $agent->transcripts()->avg('cs_rating') ?? 0;

            // Prepare active statuses for metrics service
            $activeStatuses = [
                'scheduled' => $statusCounts['Scheduled'],
                'shipped' => $statusCounts['Shipped'],
                'delivered' => $statusCounts['Delivered'],
                'returned' => $statusCounts['Returned'],
            ];

            // Generate metrics
            try {
                $metrics = $this->metricsService->generateMetrics('CallAgent', [
                    'totalOrders' => $totalOrders,
                    'outOfStock' => 0,
                    'delivered' => $statusCounts['Delivered'],
                    'cancelled' => $statusCounts['Cancelled'],
                    'pending' => $statusCounts['Pending'],
                    'returned' => $statusCounts['Returned'],
                    'statuses' => $activeStatuses,
                ]);

                \Log::debug('Metrics generated', [
                    'agent_id' => $agent->id,
                    'total_orders' => $totalOrders,
                    'delivery_rate' => $metrics['DeliveryRate'] ?? 0
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to generate metrics', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }

            // Combine metrics with agent data
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

        \Log::info('Agent metrics calculation completed', [
            'total_agents_processed' => count($agentMetrics)
        ]);

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
