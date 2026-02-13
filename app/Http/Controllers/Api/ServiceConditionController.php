<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceConditionRequest;
use App\Http\Requests\UpdateServiceConditionRequest;
use App\Models\ServiceCondition;

class ServiceConditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceConditions = ServiceCondition::with(['service', 'conditionType'])->get()->map(function ($sc) {
            return array_merge(
                $sc->toArray(),
                [
                    'service_name' => $sc->service->name ?? null,
                    'condition_type_name' => $sc->conditionType->name ?? null,
                ]
            );
        });

        return response()->json($serviceConditions, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceConditionRequest $request)
    {
        $serviceCondition = ServiceCondition::create($request->validated());

        return response()->json($serviceCondition, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceConditionRequest $request, $id)
    {
        $serviceCondition = ServiceCondition::findOrFail($id);

        $serviceCondition->update($request->validated());

        return response()->json($serviceCondition, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $serviceCondition = ServiceCondition::findOrFail($id);

        $serviceCondition->delete();

        return response()->json(null, 204);
    }
}
