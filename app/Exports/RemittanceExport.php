<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RemittanceExport implements FromCollection, WithHeadings
{
    protected $remittance;

    public function __construct($remittance)
    {
        $this->remittance = $remittance;
    }

    public function collection()
    {
        // ✅ Always safe collection (prevents "all() on null")
        $orders = collect($this->remittance->remittanceOrders ?? []);

        return $orders->map(function ($item, $index) {

            $order = $item->order ?? null;
            $customer = $order->customer ?? null;

            // ✅ FIX: correct Laravel relationship name
            $orderItems = collect($order->orderItems ?? []);

            // ✅ Merge items (no repetition)
            $items = $orderItems
                ->map(fn($i) => ($i->sku ?? 'Item') . ' x' . ($i->quantity ?? 0))
                ->implode(', ');

            // ✅ Quantity total
            $qty = $orderItems->sum('quantity');

            // ✅ Charges safe
            $charges = collect($item->charges ?? []);
            $deliveryFee = $charges->sum('amount');

            return [
                'No' => $index + 1,
                'Order No' => $order->order_no ?? $order->id ?? '-',
                'COD Amount' => $item->cod_amount ?? 0,
                'Qty' => $qty,
                'Items' => $items ?: '-',

                'Customer Name' => $customer->full_name ?? '-',
                'Phone' => $customer->phone ?? '-',
                'Location' => $customer->address ?? '-',

                'Status' => $order->latest_status->status->name ?? '-',
                'Status Date' => $order->latest_status->created_at ?? '-',

                'Delivery Fee' => $deliveryFee,
                'Total Fees' => $item->total_charges ?? 0,
                'Net Remit' => $item->net_remit ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Order No',
            'COD Amount',
            'Qty',
            'Items',
            'Customer Name',
            'Phone',
            'Location',
            'Status',
            'Status Date',
            'Delivery Fee',
            'Total Fees',
            'Net Remit',
        ];
    }
}
