<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $couriers = Courier::paginate(15);
        return CourierResource::collection($couriers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourierRequest $request)
    {
        $courier = Courier::create($request->validated());
        return new CourierResource($courier);
    }

    /**
     * Display the specified resource.
     */
    public function show(Courier $courier)
    {
        return new CourierResource($courier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourierRequest $request, Courier $courier)
    {
        $courier->update($request->validated());
        return new CourierResource($courier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Courier $courier)
    {
        $courier->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
