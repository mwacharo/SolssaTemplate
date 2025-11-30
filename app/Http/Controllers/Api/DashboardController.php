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

    public function index()
    {
        return response()->json([
            'orderStats'     => $this->service->getOrderStats(),
            'orderChart'     => $this->service->getOrderAnalytics(),
            'inventory'      => $this->service->getInventoryStats(),
            'statusData'     => $this->service->getStatusOverview(),
            // 'topAgents'      => $this->service->getTopAgents(),
            // 'deliveryRate'   => $this->service->getDeliveryRate(),
            'topProducts'    => $this->service->getTopProducts(),
            'topSellers'     => $this->service->getTopSellers(),
            'wallet'         => $this->service->getWalletEarnings(),
            'placeholder'    => null,
        ]);
    }
}
