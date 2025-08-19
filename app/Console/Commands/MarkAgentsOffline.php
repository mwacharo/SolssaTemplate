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
        // Log::info('MarkAgentsOffline job started.');
        // fetch all users 

        // who are ready, busy or have no status set and have not been seen in the last minute
        // and update their status to offline

        $allusers = User::all();
        // Log::info('Total users fetched: ' . $allusers->count());
        // Log::info('Users:', $allusers->toArray());

        // $inactiveAgents = User::whereIn('status', ['ready', 'busy', ''])
        //     ->where('last_seen_at', '<', Carbon::now()->subMinutes(1))
        //     ->get();


        $inactiveAgents = User::where(function ($query) {
            $query->whereIn('status', ['ready', 'busy', ''])
                ->orWhereNull('status');
        })
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', Carbon::now()->subMinutes(1));
            })
            ->get();


        foreach ($inactiveAgents as $agent) {
            $agent->update(['status' => 'offline']);
            broadcast(new AgentStatusUpdated($agent))->toOthers();
        }

        // Log::info('MarkAgentsOffline job finished. Inactive agents processed: ' . $inactiveAgents->count());
    }
}
