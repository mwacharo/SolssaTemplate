<?php

namespace App\Console\Commands;

use App\Events\AgentStatusUpdated;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkAgentsOffline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-agents-offline';
    protected $description = 'Mark agents offline if inactive';

    public function handle()
    {
        Log::info('MarkAgentsOffline job started.');

        $inactiveAgents = User::whereIn('status', ['ready', 'busy', ''])
            ->where('last_seen_at', '<', Carbon::now()->subMinutes(1))
            ->get();

        foreach ($inactiveAgents as $agent) {
            $agent->update(['status' => 'offline']);
            broadcast(new AgentStatusUpdated($agent))->toOthers();
        }

        Log::info('MarkAgentsOffline job finished. Inactive agents processed: ' . $inactiveAgents->count());
    }
}
