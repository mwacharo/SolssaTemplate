<?php

namespace App\Console\Commands;

use App\Events\AgentStatusUpdated;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $inactiveAgents = User::whereIn('status', ['ready', 'busy'])
            ->where('last_seen_at', '<', Carbon::now()->subMinutes(1))
            ->get();

        foreach ($inactiveAgents as $agent) {
            $agent->update(['status' => 'offline']);
            broadcast(new AgentStatusUpdated($agent))->toOthers();
        }
    }
}
