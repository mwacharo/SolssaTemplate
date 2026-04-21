<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch List</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
            padding: 20px 30px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 12px;
        }

        .logo img {
            height: 55px;
            width: auto;
        }

        .agent-name {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            flex: 1;
            padding-top: 10px;
        }

        .company-info {
            text-align: right;
            font-size: 11px;
            line-height: 1.6;
            color: #444;
        }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead tr {
            background-color: #f2f2f2;
        }

        thead th {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        tbody tr {
            border: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        tbody td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            font-size: 11px;
            vertical-align: top;
        }

        /* ── Summary Row ── */
        .summary {
            margin-top: 12px;
            font-size: 12px;
            color: #333;
        }

        .summary span {
            margin-right: 24px;
        }

        .summary strong {
            font-weight: bold;
        }

        /* ── Signature Block ── */
        .signature-block {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 16px;
        }

        .sig-row {
            display: flex;
            gap: 60px;
            margin-bottom: 20px;
        }

        .sig-field {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            font-size: 12px;
        }

        .sig-field label {
            font-weight: bold;
            white-space: nowrap;
        }

        .sig-line {
            border-bottom: 1px solid #333;
            width: 160px;
            min-height: 18px;
        }

        .sig-value {
            border-bottom: 1px solid #333;
            min-width: 160px;
            min-height: 18px;
            font-size: 12px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ── --}}
    <div class="header">
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


         <div class="agent-name">
            {{ $agentName ?? 'Agent Name' }}
        </div>
    </div>

    {{-- ── ORDERS TABLE ── --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Order No.</th>
                {{-- <th>Tracking number</th> --}}
                <th>Total</th>
                <th>Product</th>
                <th>Client</th>
                <th>Address</th>
                <th>Phone</th>
                {{-- status --}}
                    <th>Status</th>
                    {{-- <th>Assigned to</th> --}}
                    {{-- <th>Dispatched at</th> --}}
                <th>Payment code</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $index => $order)
            <tr>
                <td>{{ $index + 1 }}</td>

                {{-- order_no is the actual column name --}}
                <td>{{ $order->order_no }}</td>

                {{-- total_price is the actual column name --}}
                <td>{{ number_format($order->total_price, 2) }}</td>

                {{-- orderItems is the loaded relation; implode product names --}}
<td>
    {{ $order->orderItems
        ->map(fn($item) => ($item->product?->product_name ?: $item->name ?: $item->sku) . ' x' . $item->quantity)
        ->filter()
        ->implode(', ') ?: '-' }}
</td>
                {{-- customer relation --}}
                <td>{{ $order->customer?->full_name ?? '-' }}</td>

                {{-- shippingAddress first, fall back to customer address --}}
                <td>{{ $order->shippingAddress?->address ?? $order->customer?->address ?? '-' }}</td>

                <td>{{ $order->customer?->phone ?? '-' }}</td>

                {{-- latestStatus relation --}}
                <td>{{ $order->latestStatus?->status?->name ?? '-' }}</td>
                {{-- payment code  --}}
                <td>{{ $order->payment_code ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── SUMMARY ── --}}
    <div class="summary">
        <span>Total <strong>{{ $orders->count() }}</strong></span>
        <span>delivered <strong>{{ $orders->where('status', 'delivered')->count() }}</strong></span>
        <span>in transit <s>
    </table>

   
    {{-- ── SIGNATURE BLOCK ── --}}
    <div class="signature-block">
        <div class="sig-row">
            <div class="sig-field">
               <strong>{{ $orders->where('status', 'in_transit')->count() }}</strong></span>
    </div>

    {{-- ── SIGNATURE BLOCK ── --}}
    <div class="signature-block">
        <div class="sig-row">
            <div class="sig-field">
                <label>Dispatched By</label>
                <div class="sig-line"></div>
            </div>
            <div class="sig-field">
                <label>Signature</label>
                <div class="sig-line"></div>
            </div>
            <div class="sig-field">
                <label>Date</label>
                <div class="sig-value">{{ now()->format('D d M Y') }}</div>
            </div>
        </div>

        <div class="sig-row">
            <div class="sig-field">
                <label>Agent Name</label>
                <div class="sig-value">{{ $agentName ?? 'Agent Name' }}</div>
            </div>
            <div class="sig-field">
                <label>County</label>
                <div class="sig-line"></div>
            </div>
            <div class="sig-field">
                <label>Signature</label>
                <div class="sig-line"></div>
            </div>
        </div>
    </div>

    {{-- ── FOOTER ── --}}
    <div class="footer">
        © {{ date('Y') }} 

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

</body>
</html>