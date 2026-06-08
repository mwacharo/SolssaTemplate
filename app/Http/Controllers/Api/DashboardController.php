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
            'confirmationSummary' => $this->service->getConfirmationSummaryForUser($user, $request),
            'deliverySummary'     => $this->service->getDeliverySummaryForUser($user, $request),
            'ordersGivenSummary'  => $this->service->getOrdersGivenSummary($user, $request),
            'placeholder'    => null,
        ]);
    }
}
