{{-- resources/views/waybill/bulk-template.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Waybills</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1a1a1a;
            background: #fff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .waybill-container {
            width: 80mm;
            height: 110mm;
            margin: 0 auto 6px auto;
            padding: 3px;
            background: #fff;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            position: relative;
        }
        
        .brand-section { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        
        .logo {
            width: 36px; 
            height: 36px;
            background: linear-gradient(135deg, #667eea 0%, #667eea 100%);
            border-radius: 8px;
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: white; 
            font-weight: 700; 
            font-size: 10px; 
            letter-spacing: -0.3px;
            box-shadow: 0 2px 4px rgba(102,126,234,0.25);
        }
        
        .brand-info h1 { 
            font-size: 12px; 
            font-weight: 700; 
            color: #1a1a1a; 
            margin-bottom: 1px; 
            letter-spacing: -0.3px; 
        }
        
        .brand-tagline { 
            font-size: 7px; 
            color: #6b7280; 
            font-weight: 500; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
        }
        
        .tracking-id {
            position: absolute; 
            top: 0; 
            right: 0;
            background: #f3f4f6; 
            padding: 6px 8px; 
            border-radius: 4px;
            font-family: 'Courier New', monospace; 
            font-size: 9px; 
            font-weight: 600; 
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .status-badge {
            position: absolute; 
            top: -8px; 
            right: -8px;
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white; 
            padding: 4px 8px; 
            border-radius: 4px;
            font-size: 8px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(16,185,129,0.25);
        }
        
        .content-grid {
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 8px; 
            margin-bottom: 12px;
        }
        
        .info-card {
            background: #f9fafb; 
            border-radius: 6px; 
            padding: 8px; 
            border: 1px solid #f3f4f6;
        }
        
        .info-card h3 {
            font-size: 8px; 
            font-weight: 600; 
            color: #6b7280; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
            margin-bottom: 4px;
        }
        
        .info-card .name { 
            font-size: 9px; 
            font-weight: 600; 
            color: #1a1a1a; 
            margin-bottom: 2px; 
            line-height: 1.2; 
        }
        
        .info-card .details { 
            font-size: 7px; 
            color: #6b7280; 
            line-height: 1.3; 
        }
        
        .city-tag {
            display: inline-block; 
            background: #dbeafe; 
            color: #1e40af; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-size: 7px; 
            font-weight: 600; 
            margin-top: 2px; 
            text-transform: uppercase; 
            letter-spacing: 0.2px;
        }
        
        .order-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 6px; 
            padding: 10px; 
            margin-bottom: 10px; 
            border: 1px solid #e2e8f0;
        }
        
        .summary-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 8px; 
        }
        
        .summary-header h3 { 
            font-size: 8px; 
            font-weight: 600; 
            color: #374151; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
        }
        
        .order-number { 
            font-family: 'Courier New', monospace; 
            font-size: 8px; 
            font-weight: 600; 
            color: #6b7280; 
        }
        
        .summary-grid { 
            display: grid; 
            grid-template-columns: repeat(3,1fr); 
            gap: 8px; 
        }
        
        .summary-item { 
            text-align: center; 
        }
        
        .summary-item .label { 
            font-size: 7px; 
            color: #6b7280; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
            margin-bottom: 1px; 
        }
        
        .summary-item .value { 
            font-size: 9px; 
            font-weight: 600; 
            color: #1a1a1a; 
        }
        
        .product-section { 
            margin-bottom: 10px; 
        }
        
        .section-title {
            font-size: 8px; 
            font-weight: 600; 
            color: #6b7280; 
            text-transform: uppercase; 
            letter-spacing: 0.3px;
            margin-bottom: 6px; 
            padding-bottom: 2px; 
            border-bottom: 1px solid #f3f4f6;
        }
        
        .product-item {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 6px 0; 
            border-bottom: 1px solid #f9fafb;
        }
        
        .product-item:last-child { 
            border-bottom: none; 
        }
        
        .product-name { 
            font-size: 8px; 
            font-weight: 500; 
            color: #374151; 
            flex: 1; 
            line-height: 1.2; 
        }
        
        .product-qty {
            font-size: 7px; 
            color: #6b7280; 
            background: #f3f4f6; 
            padding: 2px 4px; 
            border-radius: 3px;
            margin: 0 6px; 
            min-width: 18px; 
            text-align: center;
        }
        
        .product-service { 
            font-size: 7px; 
            color: #10b981; 
            font-weight: 500; 
        }
        
        .payment-section {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .cod-label { 
            font-size: 7px; 
            color: #92400e; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
            margin-bottom: 2px; 
        }
        
        .cod-amount { 
            font-size: 12px; 
            font-weight: 700; 
            color: #92400e; 
            margin-bottom: 2px; 
        }
        
        .payment-method { 
            font-size: 6px; 
            color: #a16207; 
        }
        
        .barcode-section {
            text-align: center; 
            margin-bottom: 10px; 
            padding: 8px; 
            background: #fff; 
            border-radius: 6px; 
            border: 1px solid #f3f4f6;
        }
        
        .barcode { 
            margin: 4px 0; 
            transform: scale(0.6); 
            transform-origin: center; 
        }
        
        .delivery-section {
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: #ecfdf5; 
            border: 1px solid #a7f3d0; 
            border-radius: 6px; 
            padding: 8px; 
            margin-bottom: 10px;
        }
        
        .delivery-date { 
            font-size: 8px; 
            font-weight: 600; 
            color: #065f46; 
        }
        
        .delivery-agent { 
            font-size: 7px; 
            color: #047857; 
        }
        
        .footer {
            background: #f9fafb; 
            border-radius: 6px; 
            padding: 8px; 
            border: 1px solid #f3f4f6;
        }
        
        .footer-title { 
            font-size: 7px; 
            font-weight: 600; 
            color: #374151; 
            margin-bottom: 4px; 
            text-transform: uppercase; 
            letter-spacing: 0.3px; 
        }
        
        .footer-content { 
            font-size: 7px; 
            color: #6b7280; 
            line-height: 1.3; 
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
            }
            
            .waybill-container { 
                margin: 0; 
                border-radius: 0; 
                box-shadow: none; 
                width: 105mm; 
                height: 148mm; 
            }
            
            @page { 
                size: A6; 
                margin: 0; 
            }
        }
    </style>
</head>
<body>
    @foreach($waybills as $waybill)
        @php
            $order = $waybill['order'];
            $barcode = $waybill['barcode'];
            $company = $waybill['company'];
            $paymentMethod = $order->payment_method ?? 'cod';
            $statusColors = [
                'delivered' => ['#10b981', '#059669'],
                'in_transit' => ['#3b82f6', '#1d4ed8'],
                'pending' => ['#f59e0b', '#d97706'],
                'cancelled' => ['#ef4444', '#dc2626'],
                'processing' => ['#8b5cf6', '#7c3aed'],
                'shipped' => ['#06b6d4', '#0891b2'],
                'confirmed' => ['#10b981', '#059669']
            ];
            $currentStatus = $order->status ?? 'pending';
            $colors = $statusColors[$currentStatus] ?? ['#6b7280', '#4b5563'];
        @endphp
        
        <div class="waybill-container">
            <div class="status-badge" style="background: linear-gradient(135deg, {{ $colors[0] }} 0%, {{ $colors[1] }} 100%);">
                {{ ucfirst(str_replace('_', ' ', $order->status ?? 'pending')) }}
            </div>
            
            <!-- Header -->
            <div class="header">
                <div class="brand-section">
                    <div class="logo" style="background: linear-gradient(135deg, {{ $company->brand_color ?? '#667eea' }} 0%, {{ $company->brand_color ?? '#667eea' }} 100%);">
                        {{ strtoupper(substr($company->name ?? 'BL', 0, 2)) }}
                    </div>
                    <div class="brand-info">
                        <h1>{{ $company->name ?? 'BoxLeo' }}</h1>
                        <div class="brand-tagline">{{ $company->template_name ?? 'Express Courier' }}</div>
                    </div>
                </div>
                <div class="tracking-id">{{ $order->order_no }}</div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="content-grid">
                <div class="info-card">
                    <h3>Shipped From</h3>
                    <div class="name">{{ $company->name ?? 'Company Name' }}</div>
                    <div class="details">
                        {{ $company->phone ?? 'N/A' }}<br>
                        {{ $company->email ?? 'N/A' }}<br>
                        {{ $company->address ?? 'Company Address' }}
                    </div>
                </div>

                <div class="info-card">
                    <h3>Ship To</h3>
                    <div class="name">{{ $order->customer->full_name ?? 'Customer Name' }}</div>
                    <div class="details">
                        {{ $order->customer->phone ?? 'N/A' }}<br>
                        {{ $order->delivery_address ?? $order->customer->address ?? 'Delivery Address' }}<br>
                        @if(optional($order->customer->city)->name || optional($order->customer->zone)->name)
                            {{ optional($order->customer->city)->name ?? '' }}
                            @if(optional($order->customer->city)->name && optional($order->customer->zone)->name)
                                - 
                            @endif
                            {{ optional($order->customer->zone)->name ?? '' }}
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-header">
                    <h3>Order Summary</h3>
                    <div class="order-number">{{ $order->order_no }}</div>
                </div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Date</div>
                        <div class="value">
                            @php
                                try {
                                    if ($order->created_at instanceof \Carbon\Carbon) {
                                        echo $order->created_at->format('d/m/Y');
                                    } elseif (is_string($order->created_at)) {
                                        echo \Carbon\Carbon::parse($order->created_at)->format('d/m/Y');
                                    } else {
                                        echo date('d/m/Y');
                                    }
                                } catch (\Exception $e) {
                                    echo date('d/m/Y');
                                }
                            @endphp
                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Payment</div>
                        <div class="value">{{ strtoupper($order->payment_method ?? 'COD') }}</div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Items</div>
                        <div class="value">{{ collect($order->orderItems)->count() }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Product Section -->
            <div class="product-section">
                <div class="section-title">Items</div>
                @forelse($order->orderItems as $item)
                    <div class="product-item">
                        <div class="product-name">{{ $item->product->product_name ?? 'Product Name' }}</div>
                        <div class="product-qty">{{ $item->quantity ?? 1 }}</div>
                        <div class="product-service">{{ $item->service_type ?? 'Delivery' }}</div>
                    </div>
                @empty
                    <div class="product-item">
                        <div class="product-name">No items found</div>
                        <div class="product-qty">0</div>
                        <div class="product-service">-</div>
                    </div>
                @endforelse
            </div>
            
            <!-- Payment Section -->
            <div class="payment-section" style="background: {{ $paymentMethod == 'cod' ? '#fef3c7' : '#ecfdf5' }}; border: 1px solid {{ $paymentMethod == 'cod' ? '#fbbf24' : '#a7f3d0' }};">
                <div class="cod-label" style="color: {{ $paymentMethod == 'cod' ? '#92400e' : '#065f46' }};">
                    @if($paymentMethod == 'cod')
                        Cash on Delivery
                    @else
                        {{ ucfirst($order->payment_method ?? 'Payment') }}
                    @endif
                </div>
                <div class="cod-amount" style="color: {{ $paymentMethod == 'cod' ? '#92400e' : '#065f46' }};">
                    {{ ($order->currency ?? 'KSH') . ' ' . number_format($order->total_price ?? 0, 2) }}
                </div>
                <div class="payment-method" style="color: {{ $paymentMethod == 'cod' ? '#a16207' : '#047857' }};">
                    @if($paymentMethod == 'cod')
                        Pay upon delivery
                    @else
                        {{ ucfirst($order->payment_method ?? 'Prepaid') }}
                    @endif
                </div>
            </div>
            
            <!-- Barcode Section -->
            <div class="barcode-section">
                <div class="barcode">
                    {!! $barcode !!}
                </div>
            </div>
            
            <!-- Delivery Info -->
            <div class="delivery-section">
                <div>
                    <div class="delivery-date">
                        Expected: @php
                            try {
                                if ($order->expected_delivery_date) {
                                    if ($order->expected_delivery_date instanceof \Carbon\Carbon) {
                                        echo $order->expected_delivery_date->format('M d Y');
                                    } else {
                                        echo \Carbon\Carbon::parse($order->expected_delivery_date)->format('M d Y');
                                    }
                                } else {
                                    echo 'TBD';
                                }
                            } catch (\Exception $e) {
                                echo 'TBD';
                            }
                        @endphp
                    </div>
                    <div class="delivery-agent">
                        By: {{ $order->rider->name ?? $order->agent->name ?? 'Delivery Agent' }}
                        {!! \Illuminate\Support\Str::limit($company->terms ?? 'Standard terms and conditions apply. Contact us for any issues or concerns.', 150) !!}

                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div class="footer-title">{{ $company->footer ?? 'Terms & Conditions' }}</div>
                <div class="footer-content">
                    {!! \Illuminate\Support\Str::limit($company->terms ?? 'Standard terms and conditions apply. Contact us for any issues or concerns.', 150) !!}
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>