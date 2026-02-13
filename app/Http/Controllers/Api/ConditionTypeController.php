<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConditionTypeRequest;
use App\Http\Requests\UpdateConditionTypeRequest;
use App\Models\ConditionType;
use Illuminate\Http\JsonResponse;

class ConditionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $types = ConditionType::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $types,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConditionTypeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $conditionType = ConditionType::create($data);

        return response()->json([
            'message' => 'Condition type created.',
            'data' => $conditionType,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConditionTypeRequest $request, int $id): JsonResponse
    {
        $conditionType = ConditionType::findOrFail($id);
        $conditionType->update($request->validated());

        return response()->json([
            'message' => 'Condition type updated.',
            'data' => $conditionType,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $conditionType = ConditionType::findOrFail($id);
        $conditionType->delete();

        return response()->json(null, 204);
    }
}
