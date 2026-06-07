<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    //


    protected $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }




    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'orderStats'     => $this->service->getOrderStats($user),
            'orderChart'     => $this->service->getOrderAnalytics($user),
            // 'inventory'      => $this->service->getInventoryStats($user),
            'statusData'     => $this->service->getStatusOverview($user),
            'topAgents'      => $this->service->getTopAgents($user),
            'deliveryRate'   => $this->service->getDeliveryRate($user),
            'topProducts'    => $this->service->getTopProducts($user),
            'topSellers'     => $this->service->getTopSellers($user),
            'wallet'         => $this->service->getWalletEarnings($user),
            'confirmationSummary' => $this->service->getConfirmationSummaryForUser($user , $request),
            'deliverySummary'     => $this->service->getDeliverySummaryForUser($user),
            'ordersGivenSummary'  => $this->service->getOrdersGivenSummary($user),
            'placeholder'    => null,
        ]);
    }


    // public function index(Request $request)
    // {
    //     $user       = $request->user();
    //     $startDate  = $request->query('start_date');
    //     $endDate    = $request->query('end_date');
    //     $merchantId = $request->query('merchant_id');

    //     // ✅ Bundle filters into one array for clean passing
    //     $filters = [
    //         'start_date'  => $startDate,
    //         'end_date'    => $endDate,
    //         'merchant_id' => $merchantId,
    //     ];

    //     return response()->json([
    //         'orderStats'          => $this->service->getOrderStats($user, $filters),
    //         'orderChart'          => $this->service->getOrderAnalytics($user, $filters),
    //         'statusData'          => $this->service->getStatusOverview($user, $filters),
    //         'topAgents'           => $this->service->getTopAgents($user, $filters),
    //         'deliveryRate'        => $this->service->getDeliveryRate($user, $filters),
    //         'topProducts'         => $this->service->getTopProducts($user, $filters),
    //         'topSellers'          => $this->service->getTopSellers($user, $filters),
    //         'wallet'              => $this->service->getWalletEarnings($user, $filters),
    //         'confirmationSummary' => $this->service->getConfirmationSummaryForUser($user, $filters),
    //         'deliverySummary'     => $this->service->getDeliverySummaryForUser($user, $filters),
    //         'ordersGivenSummary'  => $this->service->getOrdersGivenSummary($user, $filters),
    //         'placeholder'         => null,
    //     ]);
    // }
}
