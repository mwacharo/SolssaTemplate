<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Remittance Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .center  { text-align: center; }
        .bold    { font-weight: bold; }
        .right   { text-align: right; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 5px;
        }

        th {
            background: #eee;
            text-align: center;
        }

        .totals-table {
            width: 40%;
            float: right;
            margin-top: 20px;
        }

        .footer-text {
            margin-top: 30px;
            font-size: 9px;
            color: #444;
        }
    </style>
</head>
<body>

    {{-- ═══════════════════════════════════════════════════════════════════════
         COMPANY HEADER
         Source: Remittance → vendor (User) → country (Country) → waybillSettings
         WaybillSetting fields used: name, phone, email, address, logo_path
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="center bold">

        @if(!empty($company['logo']))

            <img src="{{ public_path($company['logo']) }}"
                 alt="Company Logo"
                 style="max-width: 120px; height: auto; margin-bottom: 4px;"><br>
        @endif

        {{ strtoupper($company['name'] ?? 'COURIER AND FULFILLMENT SERVICES') }}<br>

        @if(!empty($company['address']))
            {{ $company['address'] }}<br>
        @endif
        @if(!empty($company['phone']))
            TEL: {{ $company['phone'] }}<br>
        @endif
        @if(!empty($company['email']))
            EMAIL: {{ $company['email'] }}<br>
        @endif
    </div>

    <br>

    {{-- ═══════════════════════════════════════════════════════════════════════
         REPORT TITLE
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="center bold">
        {{ strtoupper($remittance->vendor->name ?? '-') }} REMITTANCE REPORT<br>
        FOR THE PERIOD BETWEEN {{ $startDate }} AND {{ $endDate }}
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MAIN TABLE
         $serviceColumns = [ service_id => service_name ] — dynamic per vendor
    ═══════════════════════════════════════════════════════════════════════ --}}
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

                {{-- One column per unique service this vendor was charged for --}}
                @foreach($serviceColumns as $serviceId => $serviceName)
                    <th>{{ $serviceName }}</th>
                @endforeach

                <th>Total Fees</th>
                <th>Net Remit</th>
            </tr>
        </thead>

        <tbody>
            @php
                $grandTotalFees      = 0;
                $grandTotalCollected = 0;
                $serviceTotals       = array_fill_keys(array_keys($serviceColumns), 0);
            @endphp

            @foreach($remittance->remittanceOrders as $index => $item)
                @php
                    $order   = $item->order;
                    $charges = collect($item->charges ?? []);

                    $itemsCollection = collect($order->orderItems ?? []);
                    $itemsStr = $itemsCollection
                        ->map(fn($i) => ($i->sku ?? 'Item') . ' x' . ($i->quantity ?? 1))
                        ->implode(', ');
                    $qty = $itemsCollection->sum('quantity');

                    $customer = $order->customer ?? null;

                    // service_id => charged amount for this order
                    $chargeMap = $charges->keyBy('service_id')
                        ->map(fn($c) => (float) $c->amount);

                    $grandTotalFees      += (float) ($item->total_charges ?? 0);
                    $grandTotalCollected += (float) ($item->cod_amount    ?? 0);

                    foreach ($serviceColumns as $svcId => $svcName) {
                        $serviceTotals[$svcId] += ($chargeMap[$svcId] ?? 0);
                    }
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $order->order_no ?? $order->id ?? '-' }}</td>

                    <td class="right">
                        {{ number_format((float)($item->cod_amount ?? 0), 2) }}
                    </td>

                    <td>{{ $qty }}</td>
                    <td>{{ $itemsStr ?: '-' }}</td>

                    <td>{{ $customer->full_name ?? '-' }}</td>
                    <td>{{ $customer->address   ?? '-' }}</td>
                    <td>{{ $customer->phone     ?? '-' }}</td>

                    <td>{{ $order->latest_status->status->name ?? '-' }}</td>

                    <td>
                        {{ optional($order->latest_status->created_at ?? null)->format('Y-m-d') ?? '-' }}
                    </td>

                    {{-- One cell per dynamic service column --}}
                    @foreach($serviceColumns as $svcId => $svcName)
                        <td class="right">
                            {{ number_format($chargeMap[$svcId] ?? 0, 2) }}
                        </td>
                    @endforeach

                    <td class="right">
                        {{ number_format((float)($item->total_charges ?? 0), 2) }}
                    </td>

                    <td class="right">
                        {{ number_format((float)($item->net_remit ?? 0), 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- ── Column totals row ────────────────────────────────────────── --}}
            <tr>
                <td colspan="10" class="right bold">TOTALS</td>

                @foreach($serviceColumns as $svcId => $svcName)
                    <td class="right bold">
                        {{ number_format($serviceTotals[$svcId] ?? 0, 2) }}
                    </td>
                @endforeach

                <td class="right bold">{{ number_format($grandTotalFees, 2) }}</td>
                <td class="right bold">{{ number_format($grandTotalCollected - $grandTotalFees, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══════════════════════════════════════════════════════════════════════
         SUMMARY BOX (bottom-right)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <br><br>

    <table class="totals-table">
        <tr>
            <td class="bold">TOTAL FEES</td>
            <td class="right bold">{{ number_format($grandTotalFees, 2) }}</td>
        </tr>
        <tr>
            <td class="bold">TOTAL COLLECTED</td>
            <td class="right bold">{{ number_format($grandTotalCollected, 2) }}</td>
        </tr>
        <tr>
            <td class="bold">NET PAYABLE</td>
            <td class="right bold">
                {{ number_format($grandTotalCollected - $grandTotalFees, 2) }}
            </td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════════════════════════════
         FOOTER / TERMS  — from waybillSettings (optional)
    ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- @if(!empty($company['terms']))
        <div class="footer-text" style="clear:both; margin-top: 40px;">
            <strong>Terms &amp; Conditions:</strong><br>
            {{ $company['terms'] }}
        </div>
    @endif

    @if(!empty($company['footer']))
        <div class="footer-text center" style="clear:both; margin-top: 10px;">
            {{ $company['footer'] }}
        </div>
    @endif --}}

</body>
</html>