{{-- resources/views/waybill/bulk-template.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Waybills</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            background: #fff;
        }

        /* ── One waybill = one A5 page ─────────────────── */
        .waybill {
            width: 540px;       /* ~148mm at 96dpi — hard pixel width */
            padding: 24px;
            background: #fff;
            overflow: hidden;
            page-break-after: always;
        }

        /* Remove page break on the very last waybill */
        .waybill:last-child {
            page-break-after: avoid;
        }

        /* ── Header table: Logo | Shipped From | Ship To ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .col-logo  { width: 140px; padding-right: 10px !important; }
        .col-from  { width: 195px; padding-right: 10px !important; }
        .col-to    { width: 165px; }

        .logo-box {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            display: inline-block;
            line-height: 52px;
            text-align: center;
            color: #fff;
            font-weight: 800;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .logo-img {
            width: 152px;
            height: 104px;
            object-fit: contain;
            border-radius: 6px;
            display: block;
            margin-bottom: 4px;
        }

        .company-name {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
        }

        .company-sub {
            font-size: 7px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .addr-label {
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .addr-body {
            font-size: 8.5px;
            color: #333;
            line-height: 1.6;
            word-break: break-word;
        }

        /* Divider below header */
        .header-divider {
            border: none;
            border-top: 1.5px solid #333;
            margin: 0 0 10px 0;
        }

        /* ── Data tables (order info + products) ────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5px;
            table-layout: fixed;
        }

        .data-table th {
            background: #f0f0f0;
            font-weight: 600;
            color: #222;
            padding: 5px 5px;
            text-align: center;
            border: 1px solid #aaa;
            word-break: break-word;
        }

        .data-table td {
            padding: 5px 5px;
            border: 1px solid #aaa;
            color: #111;
            vertical-align: top;
            text-align: center;
            word-break: break-word;
            overflow: hidden;
        }

        .text-left { text-align: left !important; }

        /* Order info table: 5 columns */
        .t-info th:nth-child(1), .t-info td:nth-child(1) { width: 20%; }
        .t-info th:nth-child(2), .t-info td:nth-child(2) { width: 18%; }
        .t-info th:nth-child(3), .t-info td:nth-child(3) { width: 14%; }
        .t-info th:nth-child(4), .t-info td:nth-child(4) { width: 34%; }
        .t-info th:nth-child(5), .t-info td:nth-child(5) { width: 14%; }

        /* Products table: 3 columns */
        .t-prod th:nth-child(1), .t-prod td:nth-child(1) { width: 55%; }
        .t-prod th:nth-child(2), .t-prod td:nth-child(2) { width: 12%; }
        .t-prod th:nth-child(3), .t-prod td:nth-child(3) { width: 33%; }

        /* ── Barcode + COD ──────────────────────────────── */
        .barcode-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .barcode-cell {
            display: table-cell;
            vertical-align: bottom;
            width: 260px;
        }

        .barcode-cell svg,
        .barcode-cell img {
            display: block;
            max-width: 230px;
            height: auto;
        }

        .barcode-id {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            font-weight: 700;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .cod-cell {
            display: table-cell;
            vertical-align: bottom;
            font-size: 11px;
            padding-bottom: 4px;
        }

        /* ── Delivery ────────────────────────────────────── */
        .delivery-label { font-size: 8.5px; color: #555; }
        .delivery-date  { font-size: 10px; font-weight: 700; color: #16a34a; }
        .delivery-note  { font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 2px; }

        /* ── Terms ───────────────────────────────────────── */
        .terms-block {
            border-top: 1px solid #ccc;
            padding-top: 5px;
            margin-top: 6px;
            font-size: 7.5px;
            color: #444;
            line-height: 1.5;
            word-break: break-word;
        }

        .terms-title { font-weight: 700; color: #111; margin-bottom: 2px; }

        /* ── Print ────────────────────────────────────────── */
        @media print {
            html, body { margin: 0; padding: 0; }
            @page { size: A5; margin: 0; }
            .waybill { width: 100%; }
        }
    </style>
</head>
<body>

@foreach($waybills as $waybill)
@php
    $order         = $waybill['order'];
    $barcode       = $waybill['barcode'];
    $company       = $waybill['company'];
    $paymentMethod = strtolower($order->payment_method ?? 'cod');
    $brandColor    = $company->brand_color ?? '#667eea';
    $city          = optional($order->customer->city)->name ?? '';
    $zone          = optional($order->customer->zone)->name ?? '';
    $cityZone      = trim($city . ($city && $zone ? ' - ' : '') . $zone);
@endphp

<div class="waybill">

    {{-- ═══ HEADER: pure HTML table — Logo | Shipped From | Ship To ═══ --}}
    <table class="header-table">
        <tr>
            {{-- Logo --}}
            <td class="col-logo">
                @if(!empty($company->logo_path))

                    {{-- <img src="{{ asset($company->logo_path) }}" alt="logo" class="logo-img"> --}}

                    {{-- <img src="{{ $company->logo_path }}" alt="logo" class="logo-img"> --}}


                       <img src="{{ public_path($company->logo_path) }}" 
             alt="logo" 
             class="logo-img">

                @else
                    <div class="logo-box" style="background:{{ $brandColor }};">
                        {{ strtoupper(substr($company->name ?? 'BL', 0, 2)) }}
                    </div>
                @endif
                <div class="company-name">{{ $company->name ?? 'Compny' }}</div>
                <div class="company-sub">{{ $company->template_name ?? 'Express Courier' }}</div>
            </td>

            {{-- Shipped From --}}
            <td class="col-from">
                <div class="addr-label">Shipped From</div>
                <div class="addr-body">
                    <strong>{{ $company->name ?? 'Company Name' }}</strong><br>
                    {{ $company->phone ?? 'N/A' }}<br>
                    {{ $company->email ?? 'N/A' }}<br>
                    {{ $company->address ?? 'Company Address' }}
                </div>
            </td>

            {{-- Ship To --}}
            <td class="col-to">
                <div class="addr-label">Ship To</div>
                <div class="addr-body">
                    <strong>{{ $order->customer->full_name ?? 'Customer Name' }}</strong><br>
                    {{ $order->customer->phone ?? 'N/A' }}<br>
                    {{ $order->delivery_address ?? $order->customer->address ?? 'N/A' }}<br>
                    {{ $cityZone }}
                </div>
            </td>
        </tr>
    </table>

    <hr class="header-divider">

    {{-- ═══ ORDER INFO TABLE ═══ --}}
    <table class="data-table t-info">
        <thead>
            <tr>
                <th>Order date</th>
                <th>Order number</th>
                <th>Payment<br>method</th>
                <th class="text-left">Shipping Address</th>
                <th>City &Zone</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @php
                        try {
                            $d = $order->created_at instanceof \Carbon\Carbon
                                ? $order->created_at
                                : \Carbon\Carbon::parse($order->created_at);
                            echo $d->format('D d M Y') . '<br>' . $d->format('H:i');
                        } catch (\Exception $e) { echo date('D d M Y H:i'); }
                    @endphp
                </td>
                <td>{{ $order->order_no }}</td>
                <td>{{ strtoupper($paymentMethod) }}</td>
                <td class="text-left">{{ $order->delivery_address ?? $order->customer->address ?? 'N/A' }}</td>
                <td>{{ $cityZone ?: 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ═══ PRODUCTS TABLE ═══ --}}
    <table class="data-table t-prod">
        <thead>
            <tr>
                <th class="text-left">Product name</th>
                <th>Quantity</th>
                <th class="text-left">Mode of service</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->orderItems as $item)
                <tr>
                    <td class="text-left">{{ $item->product->product_name ?? 'Product Name' }}</td>
                    <td>{{ $item->quantity ?? 1 }}</td>
                    <td class="text-left">{{ $item->service_type ?? 'Delivery service' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center;color:#888;">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ═══ BARCODE + COD ═══ --}}
    <div class="barcode-row">
        <div class="barcode-cell">
            {!! $barcode !!}
            <div class="barcode-id">{{ $order->order_no }}</div>
        </div>
        <div class="cod-cell">
            COD Amount: <strong>
                @if($paymentMethod === 'cod')
                    {{ ($order->currency ?? 'KSH') . ' ' . number_format($order->total_price ?? 0, 2) }}
                @else
                    {{ strtoupper($paymentMethod) }}
                @endif
            </strong>
        </div>
    </div>

    {{-- ═══ DELIVERY INFO ═══ --}}
    <div style="margin-bottom: 6px;">
        <div class="delivery-label">Expected delivery date</div>
        <div class="delivery-date">
            @php
                try {
                    if ($order->delivery_date) {
                        $ed = $order->delivery_date instanceof \Carbon\Carbon
                            ? $order->delivery_date
                            : \Carbon\Carbon::parse($order->delivery_date);
                        echo $ed->format('D d M Y');
                    } else { echo 'TBD'; }
                } catch (\Exception $e) { echo 'TBD'; }
            @endphp
        </div>
        @if(!empty($order->rider->name) || !empty($order->agent->name))
            <div class="delivery-note">{{ $order->rider->name ?? $order->agent->name }}</div>
        @endif
        @if(!empty($order->notes))
            <div class="delivery-note">{{ $order->notes }}</div>
        @endif
    </div>

    {{-- ═══ TERMS ═══ --}}
    <div class="terms-block">
        {{-- <div class="terms-title">{{ $company->footer ?? 'Terms &amp; Conditions' }}</div> --}}

                <div class="terms-title"> 'Terms &amp; Conditions'</div>

        <div>{!! \Illuminate\Support\Str::limit($company->terms ?? 'Standard terms and conditions apply.', 350) !!}</div>
    </div>

    {{-- add footer --}}

    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid #ddd; font-size: 7.5px; color: #666; text-align: center;">
        {{ $company->footer ?? '' }}
    </div>

</div>{{-- /waybill --}}
@endforeach

</body>
</html>