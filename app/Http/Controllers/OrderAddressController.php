<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderAddressRequest;
use App\Http\Requests\UpdateOrderAddressRequest;
use App\Models\OrderAddress;

class OrderAddressController extends Controller
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
    public function store(StoreOrderAddressRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderAddress $orderAddress)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderAddress $orderAddress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderAddressRequest $request, OrderAddress $orderAddress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderAddress $orderAddress)
    {
        //
    }
}
