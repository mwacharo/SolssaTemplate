<?php

namespace App\Repositories\Interfaces;

use App\Models\GoogleSheet;

interface OrderRepositoryInterface
{
    public function getOrdersByVendorAndStatus($vendorId, $statuses = []);
    public function saveOrderData($orderData, $userId, $sheet);
}





