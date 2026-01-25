<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $services = Service::orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $services
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        $data = $request->validated();

        $service = Service::create($data);

        return response()->json([
            'message' => 'Service created successfully.',
            'data' => $service
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, $id)
    {
        $data = $request->validated();

        // use findOrFail as requested
        $service = Service::findOrFail($id);

        $service->update($data);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => $service
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // use findOrFail as requested
        $service = Service::findOrFail($id);

        $service->delete();

        return response()->json(null, 204);
    }
}
