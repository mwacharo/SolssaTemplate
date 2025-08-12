<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\User;


class AgentStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

   

    public $agentId;
    public $status;

   public function __construct(User $agent)
    {
        $this->agentId = $agent->id;
        $this->status = $agent->status;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('agent.status');
    }

    public function broadcastAs()
    {
        return 'status.updated';
    }
}
