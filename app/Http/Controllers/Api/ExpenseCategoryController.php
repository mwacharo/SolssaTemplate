<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ExpenseCategory::all();

        return response()->json([
            'data' => $categories,
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExpenseCategoryRequest $request)
    {
        $data = $request->validated();

        $category = ExpenseCategory::create($data);

        return response()->json([
            'data' => $category,
        ], 201);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseCategoryRequest $request, $id)
    {
        $data = $request->validated();

        $expenseCategory = ExpenseCategory::findOrFail($id);
        $expenseCategory->update($data);

        return response()->json([
            'data' => $expenseCategory,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $expenseCategory = ExpenseCategory::findOrFail($id);
        $expenseCategory->delete();

        return response()->json(null, 204);
    }
}
