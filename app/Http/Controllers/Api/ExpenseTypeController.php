<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseTypeRequest;
use App\Http\Requests\UpdateExpenseTypeRequest;
use App\Models\ExpenseType;

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $query = ExpenseType::query();

        if ($search = request()->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $expenseTypes = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());

        return response()->json($expenseTypes);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseTypeRequest $request)
    {
        $expenseType = ExpenseType::create($request->validated());

        return response()->json([
            'message' => 'Expense type created.',
            'data' => $expenseType,
        ], 201);
    }




    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseTypeRequest $request, $id)
    {
        $expenseType = ExpenseType::findOrFail($id);
        $expenseType->update($request->validated());

        return response()->json([
            'message' => 'Expense type updated.',
            'data' => $expenseType,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $expenseType = ExpenseType::findOrFail($id);
        $expenseType->delete();

        return response()->json(null, 204);
    }
}
