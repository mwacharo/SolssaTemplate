{{-- resources/views/waybill/bulk-template.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Waybills</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            font-size: 13px;
            font-weight: 700;

            color: #000;
        }

        /* ============================= */
        /* UNIVERSAL WAYBILL CONTAINER  */
        /* ============================= */

        .waybill {
            width: 100%;
            padding: 12px;
            border: 1px solid #000;
            page-break-after: always;
        }

        .waybill:last-child {
            page-break-after: avoid;
        }

        /* ============================= */
        /* HEADER */
        /* ============================= */

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-table td {
            vertical-align: top;
            padding: 4px;
        }

        .logo-img {
            max-width: 120px;
            height: auto;
            margin-bottom: 4px;
        }

        .logo-box {
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            font-weight: bold;
            color: #fff;
            margin-bottom: 4px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 700;
        }

        .company-sub {
            font-size: 10px;
        }

        .addr-label {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .addr-body {
            font-size: 11px;
            line-height: 1.4;
            word-break: break-word;
        }

        .header-divider {
            border: none;
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        /* ============================= */
        /* TABLES */
        /* ============================= */

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            word-break: break-word;
        }

        .data-table th {
            font-weight: 700;
        }

        .text-left {
            text-align: left !important;
        }

        /* ============================= */
        /* BARCODE + COD */
        /* ============================= */

        .barcode-section {
            margin-top: 8px;
            text-align: center;
        }

        .barcode-section svg,
        .barcode-section img {
            max-width: 100%;
            height: auto;
        }

        .barcode-id {
            font-family: monospace;
            font-size: 11px;
            font-weight: 700;
            margin-top: 3px;
        }

        .cod-box {
            margin-top: 6px;
            padding: 6px;
            border: 2px solid #000;
            font-weight: 700;
            font-size: 14px;
        }

        /* ============================= */
        /* DELIVERY */
        /* ============================= */

        .delivery-label {
            font-size: 10px;
        }

        .delivery-date {
            font-size: 12px;
            font-weight: 700;
        }

        .delivery-note {
            font-size: 10px;
            margin-top: 2px;
        }

        /* ============================= */
        /* TERMS */
        /* ============================= */

        .terms-block {
            border-top: 1px solid #000;
            margin-top: 8px;
            padding-top: 4px;
            font-size: 9px;
            line-height: 1.4;
        }

        .terms-title {
            font-weight: 700;
            margin-bottom: 3px;
        }

        /* ============================= */
        /* FOOTER */
        /* ============================= */

        .waybill-footer {
            margin-top: 6px;
            font-size: 9px;
            text-align: center;
        }

        /* ============================= */
        /* PRINT SETTINGS */
        /* ============================= */

        @media print {

            html, body {
                margin: 0;
                padding: 0;
            }

            @page {
                size: auto;   /* Let printer decide */
                margin: 0;
            }

            body {
                font-size: 11px;
            }

            .waybill {
                border: 1px solid #000;
            }
        }

        /* Thermal auto-adjust */

        @media print and (max-width: 80mm) {
            body { font-size: 10px; }
            .data-table th,
            .data-table td { font-size: 9px; }
        }

        @media print and (max-width: 58mm) {
            body { font-size: 9px; }
            .data-table th,
            .data-table td { font-size: 8px; }
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
    $brandColor    = $company->brand_color ?? '#000';
    $city          = optional($order->customer->city)->name ?? '';
    $zone          = optional($order->customer->zone)->name ?? '';
    $cityZone      = trim($city . ($city && $zone ? ' - ' : '') . $zone);
@endphp

<div class="waybill">

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td width="33%">
                @if(!empty($company->logo_path))
                    <img src="{{ public_path($company->logo_path) }}" alt="logo" class="logo-img">
                @else
                    <div class="logo-box" style="background:{{ $brandColor }}">
                        {{ strtoupper(substr($company->name ?? 'CO', 0, 2)) }}
                    </div>
                @endif
                <div class="company-name">{{ $company->name ?? 'Company' }}</div>
                <div class="company-sub">{{ $company->template_name ?? '' }}</div>
            </td>

            <td width="33%">
                <div class="addr-label">SHIPPED FROM</div>
                <div class="addr-body">
                    {{ $company->phone ?? '' }}<br>
                    {{ $company->email ?? '' }}<br>
                    {{ $company->address ?? '' }}
                </div>
            </td>

            <td width="34%">
                <div class="addr-label">SHIP TO</div>
                <div class="addr-body">
                    <strong>{{ $order->customer->full_name ?? '' }}</strong><br>
                    {{ $order->customer->phone ?? '' }}<br>
                    {{ $order->delivery_address ?? $order->customer->address ?? '' }}<br>
                    {{ $cityZone }}
                </div>
            </td>
        </tr>
    </table>

    <hr class="header-divider">

    {{-- ORDER INFO --}}
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Order #</th>
                <th>Payment</th>
                <th class="text-left">Address</th>
                <th>City</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                <td>{{ $order->order_no }}</td>
                <td>{{ strtoupper($paymentMethod) }}</td>
                <td class="text-left">{{ $order->delivery_address ?? '' }}</td>
                <td>{{ $cityZone ?: 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- PRODUCTS --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-left">Product</th>
                <th>Qty</th>
                <th class="text-left">Service</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->orderItems as $item)
                <tr>
                    <td class="text-left">{{ $item->product->product_name ?? '' }}</td>
                    <td>{{ $item->quantity ?? 1 }}</td>
                    <td class="text-left">{{ $item->service_type ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No items</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- BARCODE + COD --}}
    <div class="barcode-section">
        {!! $barcode !!}
        <div class="barcode-id">{{ $order->order_no }}</div>

        @if($paymentMethod === 'cod')
            <div class="cod-box">
                COD: {{ ($order->currency ?? 'KES') . ' ' . number_format($order->total_price ?? 0, 2) }}
            </div>
        @endif
    </div>

    {{-- DELIVERY --}}
    <div style="margin-top:6px;">
        <div class="delivery-label">Expected Delivery</div>
        <div class="delivery-date">
            {{ optional($order->delivery_date)->format('d M Y') ?? 'TBD' }}
        </div>

        @if(!empty($order->rider->name))
            <div class="delivery-note">{{ $order->rider->name }}</div>
        @endif

        @if(!empty($order->notes))
            <div class="delivery-note">{{ $order->notes }}</div>
        @endif
    </div>

    {{-- TERMS --}}
    <div class="terms-block">
        <div class="terms-title">Terms & Conditions</div>
        <div>{!! \Illuminate\Support\Str::limit($company->terms ?? '', 350) !!}</div>
    </div>

    {{-- FOOTER --}}
    <div class="waybill-footer">
        {{ $company->footer ?? '' }}
    </div>

</div>

@endforeach

</body>
</html>