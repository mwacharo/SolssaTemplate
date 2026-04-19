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
            'vendor.country.waybillSettings',
            'remittanceOrders.order.customer',
            'remittanceOrders.order.orderItems',
            'remittanceOrders.charges.service',
            'remittanceOrders.order.latest_status.status',
        ])->findOrFail($id);

        // Identical logic to downloadPdf() — same $serviceColumns, same $company
        $serviceColumns = $remittance->remittanceOrders
            ->flatMap(fn($ro) => $ro->charges ?? collect())
            ->filter(fn($charge) => $charge->service !== null)
            ->mapWithKeys(fn($charge) => [
                $charge->service_id => $charge->service->service_name,
            ])
            ->sortKeys()
            ->all();

        $company = $this->getCompanyDetails($remittance->vendor ?? null);

        $fileName = $this->generateFileName($remittance, 'xlsx');

        return Excel::download(
            new RemittanceExport(
                $remittance,
                $serviceColumns,
                $company,
                optional($remittance->payment_period_start)->format('d M Y'),
                Carbon::parse($remittance->payment_period_end)->format('d M Y'),
            ),
            $fileName
        );
    }



    public function downloadPdf($id)
    {
        $remittance = Remittance::with([
            'vendor.country.waybillSettings',           // User → Country → WaybillSetting
            'remittanceOrders.order.customer',
            'remittanceOrders.order.orderItems',
            'remittanceOrders.charges.service',
            'remittanceOrders.order.latest_status.status',
        ])->findOrFail($id);

        Log::info('Remittance Full JSON', $remittance->toArray());

        // ── 1. Dynamic service columns ────────────────────────────────────────────
        // Collect every unique service this vendor was charged for, keyed by service_id.
        // Result: [ 6 => 'Outbound Delivery Fee', 9 => 'Return Fee', ... ]
        $serviceColumns = $remittance->remittanceOrders
            ->flatMap(fn($ro) => $ro->charges ?? collect())
            ->filter(fn($charge) => $charge->service !== null)
            ->mapWithKeys(fn($charge) => [
                $charge->service_id => $charge->service->service_name,
            ])
            ->sortKeys()
            ->all();

        // ── 2. Company details from vendor → country → waybillSettings ────────────
        $company = $this->getCompanyDetails($remittance->vendor ?? null);

        // ── 3. Render PDF ─────────────────────────────────────────────────────────
        $fileName = $this->generateFileName($remittance, 'pdf');

        $pdf = Pdf::loadView('remittance.report', [
            'remittance'     => $remittance,
            'startDate'      => optional($remittance->payment_period_start)->format('d M Y'),
            'endDate'        => Carbon::parse($remittance->payment_period_end)->format('d M Y'),
            'serviceColumns' => $serviceColumns,
            'company'        => $company,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }


    // ─────────────────────────────────────────────────────────────────────────────
    // Resolve company branding from:
    //   Remittance → vendor (User) → country (Country) → waybillSettings (WaybillSetting)
    //
    // All three relationships are already eager-loaded — no extra queries.
    // ─────────────────────────────────────────────────────────────────────────────
    private function getCompanyDetails(?\App\Models\User $vendor): array
    {
        $waybill = $vendor?->country?->waybillSettings ?? null;

        return [
            'name'     => $waybill?->name      ?? 'COURIER AND FULFILLMENT SERVICES',
            'phone'    => $waybill?->phone     ?? '',
            'email'    => $waybill?->email     ?? '',
            'address'  => $waybill?->address   ?? '',
            'logo'     => $waybill?->logo_path ?? null,
            'footer'   => $waybill?->footer    ?? '',
            'terms'    => $waybill?->terms     ?? '',
        ];
    }


    private function generateFileName($remittance, $type = 'xlsx'): string
    {
        $vendor  = Str::slug($remittance->vendor->name ?? 'vendor', '_');
        $date    = Carbon::parse($remittance->payment_period_end)->format('d-m-Y');
        $invoice = $remittance->invoice_number
            ? '_INV_' . Str::slug($remittance->invoice_number)
            : '';

        return strtoupper($vendor) . '_REMITTANCE' . $invoice . '_' . $date . '.' . $type;
    }
}
