<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Requests\StoreSellerExpenseRequest;
use App\Http\Requests\UpdateSellerExpenseRequest;
use App\Models\SellerExpense;

class SellerExpenseController extends Controller
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
    public function store(StoreSellerExpenseRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SellerExpense $sellerExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SellerExpense $sellerExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSellerExpenseRequest $request, SellerExpense $sellerExpense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SellerExpense $sellerExpense)
    {
        //
    }
}
