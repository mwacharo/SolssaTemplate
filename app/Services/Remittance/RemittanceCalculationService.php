<?php

namespace App\Services\Remittance;

use App\Models\Order;
use App\Models\Remittance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RemittanceCalculationService
{
    protected int $vendorId;
    protected array $orderIds;
    protected string $from;
    protected string $to;

    public function __construct(int $vendorId, array $orderIds, string $from, string $to)
    {
        $this->vendorId = $vendorId;
        $this->orderIds = $orderIds;
        $this->from = $from;
        $this->to = $to;

        Log::info('RemittanceCalculationService initialized', [
            'vendor_id' => $vendorId,
            'order_ids' => $orderIds,
            'period' => "$from to $to"
        ]);
    }

    public function generate(): Remittance
    {
        return DB::transaction(function () {
            Log::info('Starting remittance generation');

            $orders = $this->getEligibleOrders();
            Log::info("Found {$orders->count()} eligible orders");

            if ($orders->isEmpty()) {
                Log::warning('No eligible orders found', ['vendor_id' => $this->vendorId]);
                throw new \Exception('No eligible orders found.');
            }

            $summary = $this->calculateTotals($orders);
            Log::info('Totals calculated', $summary);

            $remittance = $this->createRemittance($summary);
            Log::info('Remittance created', ['remittance_id' => $remittance->id]);


            $this->createRemittanceOrders($orders, $remittance);


            $this->attachOrders($orders, $remittance);

            return $remittance->load('orders');
        });
    }

    protected function getEligibleOrders()
    {
        return Order::with([
            'latest_status.status',
            'customer.city',
            'customer.zone',
            'vendor.services.serviceRates.serviceCondition'
        ])
            ->whereIn('id', $this->orderIds)
            ->whereNull('remittance_id')
            ->where('vendor_id', $this->vendorId)
            ->lockForUpdate()
            ->get();
    }

    protected function calculateTotals($orders): array
    {
        $resolver = new ServiceFeeResolver($this->vendorId);

        $totals = [
            'total_collected' => 0,
            'total_fees' => 0,
            'service_breakdown' => []
        ];

        foreach ($orders as $order) {
            $status = strtolower(optional($order->latest_status->status)->name);

            if ($status === 'delivered') {
                $totals['total_collected'] += $order->total_price;
                Log::debug("Order {$order->id} is delivered, added {$order->total_price}");
            }

            $fees = $resolver->calculateOrderFees($order);

            foreach ($fees as $service => $amount) {
                $totals['service_breakdown'][$service] =
                    ($totals['service_breakdown'][$service] ?? 0) + $amount;

                $totals['total_fees'] += $amount;
            }
        }

        $totals['total_remit'] =
            $totals['total_collected'] - $totals['total_fees'];

        return $totals;
    }

    protected function createRemittance(array $summary): Remittance
    {
        return Remittance::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'payment_period_start' => $this->from,
            'payment_period_end' => $this->to,
            'vendor_id' => $this->vendorId,
            'seller_id' => $this->vendorId,
            'approval_status' => 'draft',
            'total_amount' => $summary['total_remit'],
            'total_marketplace_cost' => $summary['total_fees'],
            'shipping_fee' => $summary['service_breakdown']['Outbound Delivery Fee'] ?? 0,
            'inbound_shipping_fee' => $summary['service_breakdown']['Inbound Delivery Fee'] ?? 0,
            'return_fee' => $summary['service_breakdown']['Outbound Return Fee'] ?? 0,
            'inbound_return_fee' => $summary['service_breakdown']['Inbound Return Fee'] ?? 0,
            'fulfillement_fee' => $summary['service_breakdown']['Fulfillment Fee'] ?? 0,
            'percentage_fee' => $summary['service_breakdown']['COD'] ?? 0,
            'payment_status' => 'unpaid'
        ]);
    }

    protected function attachOrders($orders, Remittance $remittance)
    {
        Order::whereIn('id', $orders->pluck('id'))
            ->update(['remittance_id' => $remittance->id]);

        Log::info("Attached {$orders->count()} orders to remittance", ['remittance_id' => $remittance->id]);
    }

    protected function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . Str::random(4);
    }


    protected function createRemittanceOrders($orders, Remittance $remittance)
    {
        $resolver = new ServiceFeeResolver($this->vendorId);

        foreach ($orders as $order) {

            $fees = $resolver->calculateOrderFees($order);

            $totalFees = array_sum($fees);

            $cod = strtolower(optional($order->latest_status->status)->name) === 'delivered'
                ? $order->total_price
                : 0;

            $remittanceOrder = \App\Models\RemittanceOrder::create([
                'remittance_id' => $remittance->id,
                'order_id' => $order->id,
                'cod_amount' => $cod,
                'total_charges' => $totalFees,
                'net_remit' => $cod - $totalFees
            ]);

            $this->createOrderCharges($remittanceOrder, $fees);
        }
    }



    protected function createOrderCharges($remittanceOrder, array $fees)
    {
        foreach ($fees as $service => $amount) {

            \App\Models\RemittanceOrderCharge::create([
                'remittance_order_id' => $remittanceOrder->id,
                'service_name' => $service,
                'amount' => $amount
            ]);
        }
    }
}
