<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignCallAgentService
{
    public function assign(Order $order)
    {
        Log::info('🚀 Call Agent Assignment started', [
            'order_id' => $order->id,
            'vendor_id' => $order->vendor_id,
        ]);

        return DB::transaction(function () use ($order) {

            $team = $order->vendor
                ->vendorFulfillmentHubs()
                ->first();


            if (!$team) {
                Log::warning('❌ No fulfillment hub found for vendor', [
                    'order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                ]);
                return null;
            }

            Log::info('🏢 Fulfillment hub selected', [
                'order_id' => $order->id,
                'hub_id' => $team->id,
                'hub_name' => $team->name ?? null,
            ]);

            $agent = $team->availableAgents()
                ->withCount([
                    'assignedOrders as today_count' => fn($q) =>
                    $q->whereDate('order_assignments.created_at', today())
                ])
                ->orderBy('today_count')
                ->orderBy('id')
                ->first();

            Log::info('Available agents', [
                'hub_id' => $team->id,
                'count' => $team->availableAgents()->count(),
                'agents' => $team->availableAgents()
                    ->pluck('users.id')
                    ->toArray(),
            ]);

            if (!$agent) {
                Log::warning('❌ No available agent found', [
                    'order_id' => $order->id,
                    'hub_id' => $team->id,
                ]);
                return null;
            }

            Log::info('👨‍💼 Agent selected', [
                'order_id' => $order->id,
                'agent_id' => $agent->id,
                'today_assignments' => $agent->today_count ?? null,
            ]);



            Log::info('📝 Order updated with agent', [
                'order_id' => $order->id,
                'agent_id' => $agent->id,
            ]);

            $assignment = OrderAssignment::create([
                'order_id' => $order->id,
                'user_id' => $agent->id,
                'role' => 'CallAgent',
                'status' => 'active',
            ]);

            Log::info('✅ OrderAssignment created', [
                'assignment_id' => $assignment->id,
                'order_id' => $order->id,
                'user_id' => $agent->id,
            ]);

            return $agent;
        });
    }
}
