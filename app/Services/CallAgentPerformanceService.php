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





    public function calculateAgentMetrics($agents): array
    {
        // \Log::info('Starting agent metrics calculation', ['agent_count' => count($agents)]);

        $agentMetrics = [];

        foreach ($agents as $agent) {
            // \Log::info('Processing agent', [
            //     'agent_id' => $agent->id,
            //     'agent_name' => $agent->name
            // ]);

            // Debug: Check raw assignment data first
            $rawAssignments = \DB::table('order_assignments')
                ->where('user_id', $agent->id)
                ->where('role', 'CallAgent')
                ->get();

            // \Log::debug('Raw assignments for agent', [
            //     'agent_id' => $agent->id,
            //     'assignment_count' => $rawAssignments->count(),
            //     'assignments' => $rawAssignments->toArray()
            // ]);

            // Debug: Check if orders relationship exists
            try {
                $relationshipTest = $agent->orders()->count();
                // \Log::debug('Orders relationship test', [
                //     'agent_id' => $agent->id,
                //     'base_orders_count' => $relationshipTest
                // ]);
            } catch (\Exception $e) {
                // \Log::error('Orders relationship error', [
                //     'agent_id' => $agent->id,
                //     'error' => $e->getMessage()
                // ]);
            }

            // Original query with enhanced debugging
            $ordersQuery = $agent->orders()
                ->join('order_assignments', function ($join) use ($agent) {
                    $join->on('orders.id', '=', 'order_assignments.order_id')
                        ->where('order_assignments.user_id', '=', $agent->id)
                        ->where('order_assignments.role', '=', 'CallAgent');
                })
                ->distinct();

            // Log the SQL query
            // \Log::debug('Orders query SQL', [
            //     'agent_id' => $agent->id,
            //     'sql' => $ordersQuery->toSql(),
            //     'bindings' => $ordersQuery->getBindings()
            // ]);

            $totalOrders = $ordersQuery->count();
            // \Log::debug('Total orders counted', [
            //     'agent_id' => $agent->id,
            //     'total_orders' => $totalOrders
            // ]);

            // Load orders with statuses
            // $orders = $agent->orders()
            //     ->join('order_assignments', function ($join) use ($agent) {
            //         $join->on('orders.id', '=', 'order_assignments.order_id')
            //             ->where('order_assignments.user_id', '=', $agent->id)
            //             ->where('order_assignments.role', '=', 'CallAgent');
            //     })
            //     ->distinct()
            //     ->with('latestStatus.status')
            //     ->get();


            $orders = $agent->assignedOrders()->with('latestStatus.status')->get();


            // \Log::debug('Orders loaded with statuses', [
            //     'agent_id' => $agent->id,
            //     'orders_count' => $orders->count(),
            //     'order_ids' => $orders->pluck('id')->toArray()
            // ]);

            // Debug: Check if orders exist without join
            $ordersWithoutJoin = \DB::table('orders')
                ->whereIn('id', $rawAssignments->pluck('order_id'))
                ->get();

            // \Log::debug('Orders without join check', [
            //     'agent_id' => $agent->id,
            //     'orders_exist' => $ordersWithoutJoin->count(),
            //     'order_ids' => $ordersWithoutJoin->pluck('id')->toArray()
            // ]);

            $statusCounts = [
                'New' => 0,
                'Pending' => 0,
                'Scheduled' => 0,
                'Shipped' => 0,
                'Delivered' => 0,
                'Cancelled' => 0,
                'Returned' => 0,
            ];

            foreach ($orders as $order) {
                // \Log::debug('Processing order', [
                //     'agent_id' => $agent->id,
                //     'order_id' => $order->id,
                //     'has_latest_status' => isset($order->latestStatus),
                //     'latest_status_data' => $order->latestStatus ?? null
                // ]);

                $statusName = optional($order->latestStatus->status)->name ?? null;

                if ($statusName) {
                    $key = $statusName;

                    if (array_key_exists($key, $statusCounts)) {
                        $statusCounts[$key]++;
                        // \Log::debug('Status counted', [
                        //     'agent_id' => $agent->id,
                        //     'order_id' => $order->id,
                        //     'status' => $key
                        // ]);
                    } else {
                        \Log::warning('Unknown status encountered', [
                            'agent_id' => $agent->id,
                            'order_id' => $order->id,
                            'status' => $statusName
                        ]);
                    }
                } else {
                    \Log::warning('Order without status', [
                        'agent_id' => $agent->id,
                        'order_id' => $order->id
                    ]);
                }
            }

            \Log::info('Status counts calculated', [
                'agent_id' => $agent->id,
                'status_counts' => $statusCounts
            ]);

            // Call metrics
            $totalCalls = $agent->callHistories()->count();
            $avgCallTime = $agent->callHistories()->avg('durationInSeconds');
            $rating = $agent->transcripts()->avg('cs_rating') ?? 0;

            \Log::debug('Call metrics calculated', [
                'agent_id' => $agent->id,
                'total_calls' => $totalCalls,
                'avg_call_time' => $avgCallTime,
                'rating' => $rating
            ]);

            $activeStatuses = [
                'scheduled' => $statusCounts['Scheduled'],
                'shipped' => $statusCounts['Shipped'],
                'delivered' => $statusCounts['Delivered'],
                'returned' => $statusCounts['Returned'],
            ];

            \Log::debug('Active statuses prepared', [
                'agent_id' => $agent->id,
                'active_statuses' => $activeStatuses
            ]);

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

                \Log::info('Metrics generated successfully', [
                    'agent_id' => $agent->id,
                    'metrics' => $metrics
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to generate metrics', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

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

            \Log::info('Agent metrics completed', [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name
            ]);
        }

        \Log::info('All agent metrics calculated', [
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
