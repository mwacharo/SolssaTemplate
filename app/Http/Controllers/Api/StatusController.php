<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStatusRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Status::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStatusRequest $request)
    {
        $status = Status::create($request->only(['name', 'description', 'color', 'country_id']));
        return response()->json($status, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Status $status)
    {
        return response()->json($status);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStatusRequest $request, Status $status)
    {
        $status->update($request->only(['name', 'description', 'color', 'country_id']));
        return response()->json($status);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Status $status)
    {
        $status->delete();
        return response()->json(null, 204);
    }
}
