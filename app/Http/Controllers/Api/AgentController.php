<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Http\Resources\AgentResource;
use App\Models\User;

// use App\Models\Agent;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
       //
       $agents = User::role('CallAgent')->latest()->paginate(20);
       return AgentResource::collection($agents);
    }

    
}   
