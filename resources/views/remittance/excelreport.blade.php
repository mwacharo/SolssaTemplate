<html>
<head><meta charset="UTF-8"></head>
<body>
<table>

    @if(!empty($company['logo']))
    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:1px;height:70px;text-align:center;"> </td>
    </tr>
    @endif

    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:14px;font-weight:bold;text-align:center;color:#1F3864;">{{ strtoupper($company['name'] ?? 'COURIER AND FULFILLMENT SERVICES') }}</td>
    </tr>
    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:9px;text-align:center;color:#555555;">{{ $company['address'] ?? '' }}</td>
    </tr>
    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:9px;text-align:center;color:#555555;">@if(!empty($company['phone']))TEL: {{ $company['phone'] }}@endif @if(!empty($company['email']))  |  EMAIL: {{ $company['email'] }}@endif</td>
    </tr>

    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:12px;font-weight:bold;text-align:center;color:#1F3864;">{{ strtoupper($remittance->vendor->name ?? '-') }} REMITTANCE REPORT</td>
    </tr>
    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:10px;font-weight:bold;text-align:center;color:#1F3864;">FOR THE PERIOD BETWEEN {{ $startDate }} AND {{ $endDate }}</td>
    </tr>

    <tr>
        <td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:1px;"> </td>
    </tr>

    <tr>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">No</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Order No</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">COD Amount</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Qty</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Items</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Client Name</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Location</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Phone</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Status</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Status Date</th>
        @foreach($serviceColumns as $serviceId => $serviceName)
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">{{ $serviceName }}</th>
        @endforeach
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Total Fees</th>
        <th style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:center;border:1px solid #CCCCCC;">Net Remit</th>
    </tr>

    @php
        $grandTotalFees      = 0;
        $grandTotalCollected = 0;
        $serviceTotals       = array_fill_keys(array_keys($serviceColumns), 0);
    @endphp

    @foreach($remittance->remittanceOrders as $index => $item)
    @php
        $order    = $item->order;
        $charges  = collect($item->charges ?? []);
        $customer = $order->customer ?? null;
        $itemsStr = collect($order->orderItems ?? [])
            ->map(fn($i) => ($i->sku ?? 'Item') . ' x' . ($i->quantity ?? 1))
            ->implode(', ');
        $qty      = collect($order->orderItems ?? [])->sum('quantity');
        $chargeMap = $charges->keyBy('service_id')->map(fn($c) => (float) $c->amount);
        $grandTotalFees      += (float)($item->total_charges ?? 0);
        $grandTotalCollected += (float)($item->cod_amount    ?? 0);
        foreach ($serviceColumns as $svcId => $svcName) {
            $serviceTotals[$svcId] += ($chargeMap[$svcId] ?? 0);
        }
        $bg = ($index % 2 === 0) ? '#FFFFFF' : '#F2F2F2';
    @endphp
    <tr>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:center;">{{ $index + 1 }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;">{{ $order->order_no ?? $order->id ?? '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:right;">{{ number_format((float)($item->cod_amount ?? 0), 2) }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:center;">{{ $qty }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;">{{ $itemsStr ?: '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;">{{ $customer->full_name ?? '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;">{{ $customer->address ?? '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;">{{ $customer->phone ?? '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:center;">{{ $order->latest_status->status->name ?? '-' }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:center;">{{ optional($order->latest_status->created_at ?? null)->format('Y-m-d') ?? '-' }}</td>
        @foreach($serviceColumns as $svcId => $svcName)
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:right;">{{ number_format($chargeMap[$svcId] ?? 0, 2) }}</td>
        @endforeach
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:right;">{{ number_format((float)($item->total_charges ?? 0), 2) }}</td>
        <td style="background-color:{{ $bg }};border:1px solid #DDDDDD;text-align:right;">{{ number_format((float)($item->net_remit ?? 0), 2) }}</td>
    </tr>
    @endforeach

    <tr>
        <td colspan="10" style="background-color:#2E75B6;color:#FFFFFF;font-weight:bold;text-align:right;border:1px solid #1F3864;">TOTALS</td>
        @foreach($serviceColumns as $svcId => $svcName)
        <td style="background-color:#2E75B6;color:#FFFFFF;font-weight:bold;text-align:right;border:1px solid #1F3864;">{{ number_format($serviceTotals[$svcId] ?? 0, 2) }}</td>
        @endforeach
        <td style="background-color:#2E75B6;color:#FFFFFF;font-weight:bold;text-align:right;border:1px solid #1F3864;">{{ number_format($grandTotalFees, 2) }}</td>
        <td style="background-color:#2E75B6;color:#FFFFFF;font-weight:bold;text-align:right;border:1px solid #1F3864;">{{ number_format($grandTotalCollected - $grandTotalFees, 2) }}</td>
    </tr>

    @php $blankCols = 9 + count($serviceColumns); @endphp

    <tr><td colspan="{{ 12 + count($serviceColumns) }}" style="font-size:1px;"> </td></tr>

    <tr>
        <td colspan="{{ $blankCols }}"> </td>
        <td colspan="2" style="background-color:#F2F2F2;font-weight:bold;border:1px solid #CCCCCC;">TOTAL COLLECTED</td>
        <td style="background-color:#F2F2F2;font-weight:bold;text-align:right;border:1px solid #CCCCCC;">{{ number_format($grandTotalCollected, 2) }}</td>
    </tr>
    <tr>
        <td colspan="{{ $blankCols }}"> </td>
        <td colspan="2" style="background-color:#F2F2F2;font-weight:bold;border:1px solid #CCCCCC;">TOTAL FEES</td>
        <td style="background-color:#F2F2F2;font-weight:bold;text-align:right;border:1px solid #CCCCCC;">{{ number_format($grandTotalFees, 2) }}</td>
    </tr>
    <tr>
        <td colspan="{{ $blankCols }}"> </td>
        <td colspan="2" style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;border:1px solid #1F3864;">NET PAYABLE</td>
        <td style="background-color:#1F3864;color:#FFFFFF;font-weight:bold;text-align:right;border:1px solid #1F3864;">{{ number_format($grandTotalCollected - $grandTotalFees, 2) }}</td>
    </tr>

</table>
</body>
</html>