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
        Log::info('Calculating fees for order', ['orderId' => $order->id, 'total' => $order->total_price]);

        foreach ($this->vendorServices as $vs) {
            if (!$this->isApplicable($order, $vs)) {
                Log::debug('Service not applicable', ['serviceId' => $vs->id]);
                continue;
            }

            $amount = $this->computeFee($order, $vs);

            if ($amount > 0) {
                $fees[$vs->service->service_name] = $amount;
                Log::debug('Fee calculated', ['service' => $vs->service->service_name, 'amount' => $amount]);
            }
        }

        Log::info('Fees calculation complete', ['totalFees' => count($fees)]);
        return $fees;
    }

    protected function computeFee($order, $vendorService)
    {
        $orderValue = $order->total_price;

        foreach ($vendorService->service_rates as $rate) {
            $cond = $rate->service_condition;

            if ($this->matches($cond, $orderValue)) {
                $result = $this->applyRate(
                    $orderValue,
                    $rate->custom_rate ?? $cond->value,
                    $rate->rate_type ?? $cond->rate_type
                );
                Log::debug('Rate matched and applied', ['orderValue' => $orderValue, 'result' => $result]);
                return $result;
            }
        }

        return 0;
    }

    protected function matches($condition, $value)
    {
        if (!$condition) return false;

        if ($condition->operator === 'between') {
            $matches = $value >= $condition->min_value && $value <= $condition->max_value;
            Log::debug('Condition match check', ['value' => $value, 'min' => $condition->min_value, 'max' => $condition->max_value, 'result' => $matches]);
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

    // protected function isApplicable($order, $vs)
    // {
    //     return true;
    // }



    protected function isApplicable($order, $vs)
    {
        // Ensure service exists
        if (!$vs->service) {
            return false;
        }

        $serviceCode = $vs->service->code; // or service_name

        // INBOUND ORDERS
        if ($order->type === 'inbound') {

            // Delivery fee applies only if delivered
            if ($serviceCode === 'inbound_delivery_fee') {
                return $order->status === 'delivered';
            }

            // Return fee applies only if returned
            if ($serviceCode === 'inbound_return_fee') {
                return $order->status === 'returned';
            }

            return false;
        }

        // OUTBOUND ORDERS
        if ($order->type === 'outbound') {

            if ($serviceCode === 'outbound_delivery_fee') {
                return $order->status === 'delivered';
            }

            if ($serviceCode === 'outbound_return_fee') {
                return $order->status === 'returned';
            }

            return false;
        }

        return false;
    }
}
