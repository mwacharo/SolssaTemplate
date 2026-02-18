<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        $orders = Order::with([
            'assignments.user',
            'orderItems.product',
            'shippingAddress',
            'customer',
            'latestStatus.status',
        ])
            ->whereIn('id', $this->ids)
            ->get();

        $orders->each(function ($order) {

            // Normalize items
            $order->setRelation('items', $order->orderItems ?? collect());

            // Delivery Agent
            $deliveryAssignment = $order->assignments
                ->firstWhere('role', 'Delivery Agent');

            // Call Agent
            $callAssignment = $order->assignments
                ->firstWhere('role', 'CallAgent');

            // Normalize
            $order->setRelation(
                'deliveryPerson',
                $deliveryAssignment?->user
            );

            $order->setRelation(
                'agent',
                $callAssignment?->user
            );
        });

        return $orders;
    }

    public function headings(): array
    {
        return [
            'Order Date',
            'Order ID',
            'Total Amount',
            'Customer Name',
            'Address',
            'Phone',
            'Alt Phone',
            'City',
            'Zone',
            'Product Name',
            'Quantity',
            'Status',
            'Delivery Date',
            'Special Instructions',
            'Agent',
            'Delivery Person',
        ];
    }

    public function map($order): array
    {
        return [

            optional($order->created_at)->format('Y-m-d'),

            $order->order_no ?? $order->id,

            $order->total_price ?? 0,

            optional($order->customer)->full_name,

            optional($order->shippingAddress)->address
                ?? optional($order->customer)->address,

            optional($order->customer)->phone,

            optional($order->customer)->alt_phone,

            // City (name, not id)
            $order->shippingAddress?->city?->name
                ?? $order->shippingAddress?->city_name
                ?? $order->shippingAddress?->city
                ?? $order->customer?->city?->name
                ?? $order->customer?->city_name
                ?? $order->customer?->city,

            // Zone (name, not id)
            $order->shippingAddress?->zone?->name
                ?? $order->shippingAddress?->zone_name
                ?? $order->shippingAddress?->zone
                ?? $order->customer?->zone?->name
                ?? $order->customer?->zone_name
                ?? $order->customer?->zone,

            // Product Names
            $order->items
                ->pluck('product.product_name')
                ->filter()
                ->join(', '),

            // Quantities
            $order->items
                ->pluck('quantity')
                ->join(', '),

            optional($order->latestStatus?->status)->name,

            optional($order->delivery_date)->format('Y-m-d'),

            $order->customer_notes,

            optional($order->agent)->name,

            optional($order->deliveryPerson)->name,
        ];
    }
}
