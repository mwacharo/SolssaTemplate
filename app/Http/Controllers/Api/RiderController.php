<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiderRequest;
use App\Http\Requests\UpdateRiderRequest;
use App\Http\Resources\RiderResource;
// use App\Models\Rider;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        // Since a rider is a user with the "Delivery Agent" role, get all users with that role
        $riders = \App\Models\User::role('Delivery Agent')->latest()->paginate(20);

        return RiderResource::collection($riders);
    }

   
}
