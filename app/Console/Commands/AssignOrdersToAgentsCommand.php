<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use App\Services\AssignCallAgentService;

class AssignOrdersToAgentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:assign-orders-to-agents-command';

    protected $signature = 'orders:assign-call-agents';

    protected $description = 'Assign unassigned orders to call agents';

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle(AssignCallAgentService $service)
    {
        $orders = Order::query()
            ->whereDoesntHave('latestCallAgentAssignment')
            ->whereHas('latestStatus.status', fn($q) => $q->where('name', 'New'))
            ->get();

        foreach ($orders as $order) {
            $service->assign($order);
        }

        $this->info("Processed {$orders->count()} orders.");

        return self::SUCCESS;
    }
}
