<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Response;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CityResource::collection(
            City::with('country')->paginate(15)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCityRequest $request)
    {
        $city = City::create($request->validated());
        return new CityResource($city);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        return new CityResource($city);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, $id)
    {
        $city = City::findOrFail($id);
        $city->update($request->validated());
        return new CityResource($city);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $city = City::findOrFail($id);
        $city->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
