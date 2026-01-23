<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderExpenseRequest;
use App\Http\Requests\UpdateOrderExpenseRequest;
use App\Models\OrderExpense;

class OrderExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderExpenseRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderExpense $orderExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderExpense $orderExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderExpenseRequest $request, OrderExpense $orderExpense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderExpense $orderExpense)
    {
        //
    }
}
