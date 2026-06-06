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

    // public function index()
    // {

    //     // authorised user 

    //     return response()->json([
    //         'orderStats'     => $this->service->getOrderStats(),
    //         'orderChart'     => $this->service->getOrderAnalytics(),
    //         'inventory'      => $this->service->getInventoryStats(),
    //         'statusData'     => $this->service->getStatusOverview(),
    //         'topAgents'      => $this->service->getTopAgents(),
    //         'deliveryRate'   => $this->service->getDeliveryRate(),
    //         'topProducts'    => $this->service->getTopProducts(),
    //         'topSellers'     => $this->service->getTopSellers(),
    //         'wallet'         => $this->service->getWalletEarnings(),
    //         'placeholder'    => null,
    //     ]);
    // }


    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'orderStats'     => $this->service->getOrderStats($user),
            'orderChart'     => $this->service->getOrderAnalytics($user),
            'inventory'      => $this->service->getInventoryStats($user),
            'statusData'     => $this->service->getStatusOverview($user),
            'topAgents'      => $this->service->getTopAgents($user),
            'deliveryRate'   => $this->service->getDeliveryRate($user),
            'topProducts'    => $this->service->getTopProducts($user),
            'topSellers'     => $this->service->getTopSellers($user),
            'wallet'         => $this->service->getWalletEarnings($user),
            'confirmationSummary' => $this->service->getConfirmationSummaryForUser($user),
            'deliverySummary'     => $this->service->getDeliverySummaryForUser($user),
            'placeholder'    => null,
        ]);
    }
}
