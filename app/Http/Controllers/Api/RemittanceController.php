<?php

namespace App\Http\Controllers\Api;

use App\Exports\RemittanceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemittanceRequest;
use App\Http\Requests\UpdateRemittanceRequest;
use App\Models\Remittance;
use App\Services\Remittance\RemittanceCalculationService;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;




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


    public function downloadExcel($id)
    {
        $remittance = Remittance::with([
            'vendor',
            'remittanceOrders.order.customer',
            'remittanceOrders.order.orderItems',
            'remittanceOrders.charges.service',
            'remittanceOrders.order.latest_status.status',
        ])->findOrFail($id);

        $fileName = $this->generateFileName($remittance, 'xlsx');

        return Excel::download(
            new RemittanceExport($remittance),
            $fileName
        );
    }

    public function downloadPdf($id)
    {
        $remittance = Remittance::with([
            'vendor',
            'remittanceOrders.order.customer',
            'remittanceOrders.order.orderItems',
            'remittanceOrders.charges.service',
            'remittanceOrders.order.latest_status.status',
        ])->findOrFail($id);

        // log the remmittance for debughing purposes 



        // Log::info('Remittance Debug', [
        //     'id' => $remittance->id,
        //     'vendor' => $remittance->vendor->name ?? null,
        //     'orders_count' => $remittance->remittanceOrders->count(),
        // ]);

        Log::info('Remittance Full JSON', $remittance->toArray());


        $fileName = $this->generateFileName($remittance, 'pdf');

        $pdf = Pdf::loadView('remittance.report', [
            'remittance' => $remittance,
            'startDate' => optional($remittance->payment_period_start)->format('d M Y'),
            'endDate' => optional($remittance->payment_period_end)->format('d M Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }



    private function generateFileName($remittance, $type = 'xlsx')
    {
        $vendor = $remittance->vendor->name ?? 'vendor';

        // Clean vendor name (no spaces/special chars)
        $vendor = Str::slug($vendor, '_');

        $date = optional($remittance->payment_period_end)->format('d-m-Y');

        $invoice = $remittance->invoice_number
            ? '_INV_' . Str::slug($remittance->invoice_number)
            : '';

        return strtoupper($vendor)
            . '_REMITTANCE'
            . $invoice
            . '_' . $date
            . '.' . $type;
    }
}
