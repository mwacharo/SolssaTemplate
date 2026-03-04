<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemittanceRequest;
use App\Http\Requests\UpdateRemittanceRequest;
use App\Models\Remittance;
use App\Services\Remittance\RemittanceCalculationService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemittanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $remittances = Remittance::with('orders', 'vendor')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $remittances
        ]);
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
    // public function store(StoreRemittanceRequest $request)
    // {
    //     //
    // }
    public function store(StoreRemittanceRequest $request)
    {
        $service = new RemittanceCalculationService(
            $request->vendor_id,
            $request->orders,
            $request->from,
            $request->to
        );

        $remittance = $service->generate();

        return response()->json([
            'success' => true,
            'data' => $remittance
        ]);
    }




    /**
     * Display the specified resource.
     */
    public function show(Remittance $remittance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Remittance $remittance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRemittanceRequest $request, Remittance $remittance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Remittance $remittance)
    {
        //
    }
}
