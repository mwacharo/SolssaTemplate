<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waybill - Modern Design</title>
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
            background: #ffffff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .waybill-container {
            width: 105mm;
            height: 148mm;
            margin: 0 auto;
            padding: 12px;
            background: #ffffff;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* Header Section */
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: -0.3px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.25);
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
        
        /* Status Badge */
        .status-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.25);
        }
        
        /* Main Content Grid */
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
        
        /* Order Summary */
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
            grid-template-columns: repeat(3, 1fr);
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
        
        /* Product List */
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
        
        /* Payment Info */
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
        
        /* Barcode Section */
        .barcode-section {
            text-align: center;
            margin-bottom: 10px;
            padding: 8px;
            background: #ffffff;
            border-radius: 6px;
            border: 1px solid #f3f4f6;
        }
        
        .barcode {
            margin: 4px 0;
            transform: scale(0.6);
            transform-origin: center;
        }
        
        /* Delivery Info */
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
        
        /* Footer */
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
            font-size: 6px;
            color: #6b7280;
            line-height: 1.3;
        }
        
        .mpesa-highlight {
            color: #10b981;
            font-weight: 600;
        }
        
        /* QR Code */
        .qr-code {
            position: absolute;
            bottom: 12px;
            right: 12px;
            transform: scale(0.4);
            transform-origin: bottom right;
            opacity: 0.8;
        }
        
        /* Print Styles */
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
    <div class="waybill-container">
        <div class="status-badge">In Transit</div>
        
        <!-- Header -->
        <div class="header">
            <div class="brand-section">
                <div class="logo">BL</div>
                <div class="brand-info">
                    <h1>BoxLeo</h1>
                    <div class="brand-tagline">Express Courier</div>
                </div>
            </div>
            <div class="tracking-id">CHPBL0001</div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="content-grid">
            <div class="info-card">
                <h3>Shipped From</h3>
                <div class="name">Boxleo Courier & Fulfillment Services Ltd</div>
                <div class="details">
                    0761 976 581-0764<br>
                    tanzania@boxleocourier.com<br>
                    Makongo Juu, Dar es Salaam
                </div>
            </div>
            
            <div class="info-card">
                <h3>Ship To</h3>
                <div class="name">John Doe</div>
                <div class="details">
                    256753888<br>
                    Delivery Address
                </div>
                <div class="city-tag">Kampala</div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-header">
                <h3>Order Summary</h3>
                <div class="order-number">CHPBL0001</div>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">Date</div>
                    <div class="value">22/07/2025</div>
                </div>
                <div class="summary-item">
                    <div class="label">Payment</div>
                    <div class="value">COD</div>
                </div>
                <div class="summary-item">
                    <div class="label">Items</div>
                    <div class="value">1</div>
                </div>
            </div>
        </div>
        
        <!-- Product Section -->
        <div class="product-section">
            <div class="section-title">Items</div>
            <div class="product-item">
                <div class="product-name">Seeds Garden Colorful</div>
                <div class="product-qty">1</div>
                <div class="product-service">Delivery</div>
            </div>
        </div>
        
        <!-- Payment Section -->
        <div class="payment-section">
            <div class="cod-label">Cash on Delivery</div>
            <div class="cod-amount">KSH 0</div>
            <div class="payment-method">Pay upon delivery</div>
        </div>
        
        <!-- Barcode Section -->
        <div class="barcode-section">
            <div class="barcode">
                <!-- Barcode would be inserted here -->
                <svg width="120" height="30" viewBox="0 0 120 30">
                    <rect x="0" y="0" width="2" height="30" fill="#000"/>
                    <rect x="4" y="0" width="1" height="30" fill="#000"/>
                    <rect x="7" y="0" width="3" height="30" fill="#000"/>
                    <rect x="12" y="0" width="1" height="30" fill="#000"/>
                    <rect x="15" y="0" width="2" height="30" fill="#000"/>
                    <rect x="20" y="0" width="1" height="30" fill="#000"/>
                    <rect x="23" y="0" width="3" height="30" fill="#000"/>
                    <rect x="28" y="0" width="2" height="30" fill="#000"/>
                    <rect x="32" y="0" width="1" height="30" fill="#000"/>
                    <rect x="36" y="0" width="2" height="30" fill="#000"/>
                    <rect x="40" y="0" width="3" height="30" fill="#000"/>
                    <rect x="45" y="0" width="1" height="30" fill="#000"/>
                    <rect x="48" y="0" width="2" height="30" fill="#000"/>
                    <rect x="52" y="0" width="1" height="30" fill="#000"/>
                    <rect x="56" y="0" width="3" height="30" fill="#000"/>
                    <rect x="61" y="0" width="1" height="30" fill="#000"/>
                    <rect x="64" y="0" width="2" height="30" fill="#000"/>
                    <rect x="68" y="0" width="3" height="30" fill="#000"/>
                    <rect x="73" y="0" width="1" height="30" fill="#000"/>
                    <rect x="76" y="0" width="2" height="30" fill="#000"/>
                    <rect x="80" y="0" width="1" height="30" fill="#000"/>
                    <rect x="84" y="0" width="3" height="30" fill="#000"/>
                    <rect x="89" y="0" width="1" height="30" fill="#000"/>
                    <rect x="92" y="0" width="2" height="30" fill="#000"/>
                    <rect x="96" y="0" width="1" height="30" fill="#000"/>
                    <rect x="100" y="0" width="3" height="30" fill="#000"/>
                    <rect x="105" y="0" width="2" height="30" fill="#000"/>
                    <rect x="109" y="0" width="1" height="30" fill="#000"/>
                    <rect x="112" y="0" width="3" height="30" fill="#000"/>
                    <rect x="117" y="0" width="2" height="30" fill="#000"/>
                </svg>
            </div>
        </div>
        
        <!-- Delivery Info -->
        <div class="delivery-section">
            <div>
                <div class="delivery-date">Expected: May 23 2025</div>
                <div class="delivery-agent">By: Alva O'Conner</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-title">Terms & Conditions</div>
            <div class="footer-content">
                <span class="mpesa-highlight">M-PESA LIPA NAMBA 516559</span>. Return within 12 hours of delivery. 
                Contact us within 12 hours of receiving the order for any issues or concerns.
            </div>
        </div>
    </div>
</body>
</html>