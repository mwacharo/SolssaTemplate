<?php

namespace App\Services\Remittance;

use App\Models\ServiceCondition;
use App\Models\VendorService;
use Illuminate\Support\Facades\Log;

class ServiceFeeResolver
{
    protected $vendorId;
    protected $vendorServices;
    protected $conditions;

    public function __construct($vendorId)
    {
        $this->vendorId = $vendorId;

        $this->vendorServices = VendorService::with('serviceRates.serviceCondition')
            ->where('vendor_id', $vendorId)
            ->where('is_active', 1)
            ->get();

        $this->conditions = ServiceCondition::all();

        Log::info('ServiceFeeResolver initialized', ['vendorId' => $vendorId, 'servicesCount' => $this->vendorServices->count()]);
    }




    public function calculateOrderFees($order): array
    {
        $fees = [];

        foreach ($this->vendorServices as $vs) {

            if (!$this->isApplicable($order, $vs)) {
                continue;
            }

            $fee = $this->computeFee($order, $vs);

            if ($fee && $fee['amount'] > 0) {

                $fees[$vs->service->service_name] = $fee;
            }
        }

        return $fees;
    }




    protected function computeFee($order, $vendorService)
    {
        $orderValue = (float) $order->total_price;

        $rates = $vendorService->serviceRates ?? [];

        if (empty($rates)) {
            Log::debug('No rates for service', ['serviceId' => $vendorService->id]);
            return 0;
        }




        foreach ($rates as $rate) {

            $cond = $rate->serviceCondition;

            if ($this->matches($cond, $orderValue)) {

                $rateValue = $rate->custom_rate ?? $cond->value;
                $type = $rate->rate_type ?? $cond->rate_type;

                $result = $this->applyRate($orderValue, $rateValue, $type);

                return [
                    'amount' => $result,
                    'rate_value' => $rateValue,
                    'rate_type' => $type,
                ];
            }
        }

        return 0;
    }



    protected function matches($condition, $value)
    {
        if (!$condition) return false;

        // Convert value safely (important if DB returns string)
        $value = (float) $value;

        // ✅ CASE 1: No min & max → always match (FIX for your issue)
        if (is_null($condition->min_value) && is_null($condition->max_value)) {
            Log::debug('Condition match: no min/max → TRUE', [
                'value' => $value
            ]);
            return true;
        }

        // Default operator
        $operator = $condition->operator ?? 'between';

        if ($operator === 'between') {

            $min = $condition->min_value !== null ? (float) $condition->min_value : 0;
            $max = $condition->max_value !== null ? (float) $condition->max_value : PHP_FLOAT_MAX;

            $matches = ($value >= $min && $value <= $max);

            Log::debug('Condition match check', [
                'value' => $value,
                'min' => $min,
                'max' => $max,
                'result' => $matches
            ]);

            return $matches;
        }

        return false;
    }

    protected function applyRate($orderValue, $rateValue, $type)
    {
        if ($type === 'percentage') {
            return round(($orderValue * $rateValue) / 100, 2);
        }

        return round($rateValue, 2);
    }








    protected function isApplicable($order, $vs)
    {
        if (!$vs->service) return false;

        $service = $vs->service;
        $orderType = $this->resolveOrderType($order);

        if (!$orderType) return false;

        $status = strtolower(trim(optional($order->latest_status->status)->name ?? ''));

        $isInboundService = $service->inbound == 1;

        // ✅ FILTER ONLY BY TYPE
        if ($orderType === 'inbound' && !$isInboundService) return false;
        if ($orderType === 'outbound' && $isInboundService) return false;

        // ✅ APPLY RULES WHERE NECESSARY
        return match (trim($service->service_name)) {

            'Inbound Delivery Fee',
            'Outbound Delivery Fee' => $status === 'delivered',

            'Inbound Return Fee',
            'Outbound Return Fee' => $status === 'returned',

            default => true // ✅ THIS IS CRITICAL
        };
    }



    protected function resolveOrderType($order): ?string
    {
        // Prefer city
        if ($order->customer && $order->customer->city) {
            return $order->customer->city->inbound ? 'inbound' : 'outbound';
        }

        // Fallback to zone
        if ($order->customer && $order->customer->zone) {
            return $order->customer->zone->inbound ? 'inbound' : 'outbound';
        }

        return null; // unknown
    }
}
