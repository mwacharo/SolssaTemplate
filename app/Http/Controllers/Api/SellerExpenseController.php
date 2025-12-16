<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerExpenseRequest;
use App\Http\Requests\UpdateSellerExpenseRequest;
use App\Models\SellerExpense;
use Illuminate\Http\Request;


class SellerExpenseController extends Controller
{
    /**
     * Display a listing of the seller expenses.
     */
    // public function index()
    // {
    //     $expenses = SellerExpense::with('vendor')->latest()->paginate(20);

    //     return response()->json([
    //         'success' => true,
    //         'expenses' => $expenses
    //     ]);


    // }


    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 200); // default 20

        $expenses = SellerExpense::with('vendor')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'expenses' => $expenses
        ]);
    }


    /**
     * Store a newly created seller expense.
     */
    public function store(StoreSellerExpenseRequest $request)
    {
        $expense = SellerExpense::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seller expense created successfully.',
            'data' => $expense
        ], 201);
    }

    /**
     * Display the specified seller expense.
     */
    public function show(SellerExpense $sellerExpense)
    {
        return response()->json([
            'success' => true,
            'data' => $sellerExpense
        ]);
    }

    /**
     * Update the specified seller expense.
     */
    public function update(UpdateSellerExpenseRequest $request, $id)
    {
        $sellerExpense = SellerExpense::findOrFail($id);
        $sellerExpense->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seller expense updated successfully.',
            'data' => $sellerExpense
        ]);
    }

    /**
     * Remove the specified seller expense from storage.
     */
    public function destroy($id)
    {
        $sellerExpense = SellerExpense::findOrFail($id);
        $sellerExpense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seller expense deleted successfully.'
        ]);
    }
}
