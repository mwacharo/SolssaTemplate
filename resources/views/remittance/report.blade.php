<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Remittance Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
        }

        th { background: #eee; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

    <!-- COMPANY HEADER -->
    <div class="center bold">
        COURIER AND FULFILLMENT SERVICES <br>
        TEST <br>
        EMAIL: test@com <br>
        WEBSITE: test.COM
    </div>

    <br>

    <!-- REPORT TITLE -->
    <div class="center bold">
        {{ strtoupper($remittance->vendor->name ?? '-') }} REMITTANCE REPORT <br>

        {{-- should be dymanmic   "payment_period_start": "2026-04-01T00:00:00.000000Z",
  "payment_period_end": "2026-04-12", --}}
        FOR THE PERIOD BETWEEN {{ $startDate }} AND {{ $endDate }}
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Order No</th>
                <th>COD Amount</th>
                <th>Qty</th>
                <th>Items</th>
                <th>Client Name</th>
                <th>Location</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Status Date</th>
                {{-- should be dymanic from services  the charges are not fixed --}}
                <th>Delivery Fee</th>
                <th>Total Fees</th>
                <th>Net Remit</th>
            </tr>
        </thead>

        <tbody>
            @php
                $totalFees = 0;
                $totalCollected = 0;
            @endphp

            @foreach($remittance->remittanceOrders as $index => $item)
                @php
                    $order = $item->order;
                    $charges = $item->charges ?? [];

                    // ✅ SAFE COLLECTION (fixes your crash)
                    $itemsCollection = collect($order->orderItems ?? []);

                    $items = $itemsCollection
                        ->map(fn($i) => ($i->sku ?? 'Item') . ' x' . $i->quantity)
                        ->implode(', ');

                    $qty = $itemsCollection->sum('quantity');

                    // ✅ Charges safe sum
                    $delivery = collect($charges)->sum('amount');

                    $customer = $order->customer ?? null;

                    $totalFees += $item->total_charges ?? 0;
                    $totalCollected += $item->cod_amount ?? 0;
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $order->order_no ?? $order->id ?? '-' }}</td>

                    <td class="text-right">
                        {{ number_format($item->cod_amount ?? 0, 2) }}
                    </td>

                    <td>{{ $qty }}</td>

                    <td>{{ $items ?: '-' }}</td>

                    <td>{{ $customer->full_name ?? '-' }}</td>
                    <td>{{ $customer->address ?? '-' }}</td>
                    <td>{{ $customer->phone ?? '-' }}</td>

<td>{{ $order->latest_status->status->name ?? '-' }}</td>

                    <td>
    {{ optional($order->latest_status->created_at)->format('Y-m-d') ?? '-' }}
                    </td>

                    <td class="text-right">
                        {{ number_format($delivery, 2) }}
                    </td>

                    <td class="text-right">
                        {{ number_format($item->total_charges ?? 0, 2) }}
                    </td>

                    <td class="text-right">
                        {{ number_format($item->net_remit ?? 0, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALS -->
    <br><br>

    <table style="width: 40%; float: right;">
        <tr>
            <td class="bold">TOTAL FEES</td>
            <td class="text-right bold">{{ number_format($totalFees, 2) }}</td>
        </tr>

        <tr>
            <td class="bold">TOTAL COLLECTED</td>
            <td class="text-right bold">{{ number_format($totalCollected, 2) }}</td>
        </tr>

        <tr>
            <td class="bold">NET PAYABLE</td>
            <td class="text-right bold">
                {{ number_format($totalCollected - $totalFees, 2) }}
            </td>
        </tr>
    </table>

</body>
</html>