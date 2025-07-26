<?php



namespace App\Services\Order;



use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Illuminate\Support\Facades\Storage;



class OrderBulkActionService
{
    public function assignRider(array $orderIds, int $riderId): void
    {
        Order::whereIn('id', $orderIds)->update(['rider_id' => $riderId]);
    }

    public function assignAgent(array $orderIds, int $agentId): void
    {
        Order::whereIn('id', $orderIds)->update(['agent_id' => $agentId]);
    }

    public function updateStatus(array $orderIds, string $status): void
    {
        Order::whereIn('id', $orderIds)->update(['status' => $status]);
    }



    /**
     * Print waybill for an order
     */
    public function printWaybill(Request $request, string $id)
    {
        // Eager load relationships for printing
        $order = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        // Generate barcode
        $generator = new BarcodeGeneratorHTML();
        $barcode = $generator->getBarcode($order->order_no, $generator::TYPE_CODE_128);

        // Generate QR code
        // $qrCode = QrCode::size(100)->generate($order->order_no);

        // Prepare data for PDF
        $data = [
            'order' => $order,
            'barcode' => $barcode,
            // 'qrCode' => $qrCode,
            'company' => [
                'name' => 'Boxleo Courier & Fulfillment Services Ltd',
                'phone' => '0761 976 581/0764 900539',
                'email' => 'tanzania@boxleocourier.com',
                'address' => 'Makongo juu Darajani, University Rd, Dar es Salaam',
                'terms' => 'All items must be packed securely. Boxleo Courier is not liable for damages to items not properly packed.',
                'footer' => '<div class="footer-content">
                    <span class="mpesa-highlight">M-PESA LIPA NAMBA 516559</span>. Return within 12 hours of delivery. 
                    Contact us within 12 hours of receiving the order for any issues or concerns.
                </div>',
            ]
        ];


        // Generate PDF with A5 settings
        $pdf = Pdf::loadView('waybill.template', $data)
            ->setPaper('a5', 'portrait') 
            // Changed from 'a4' to 'a5'
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'margin-top' => 0,
                'margin-right' => 0,
                'margin-bottom' => 0,
                'margin-left' => 0,
            ]);

        return $pdf->stream("waybill_{$order->order_no}.pdf");

        
    }

    /**
     * Download waybill as PDF
     */
    public function downloadWaybill(string $id)
    {
        $order = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $generator = new BarcodeGeneratorHTML();
        $barcode = $generator->getBarcode($order->order_no, $generator::TYPE_CODE_128);
        $qrCode = QrCode::size(100)->generate($order->order_no);

        $data = [
            'order' => $order,
            'barcode' => $barcode,
            'qrCode' => $qrCode,
            'company' => [
                'name' => 'Boxleo Courier & Fulfillment Services Ltd',
                'phone' => '0761 976 581/0764 900539',
                'email' => 'tanzania@boxleocourier.com',
                'address' => 'Makongo juu Darajani, University Rd, Dar es Salaam'
            ]
        ];

        $pdf = Pdf::loadView('waybill.template', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download("waybill_{$order->order_no}.pdf");
    }

    /**
     * Print waybill as HTML (for testing)
     */
    public function previewWaybill(string $id)
    {
        $order = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $generator = new BarcodeGeneratorHTML();
        $barcode = $generator->getBarcode($order->order_no, $generator::TYPE_CODE_128);
        $qrCode = QrCode::size(100)->generate($order->order_no);

        return view('waybill.template', [
            'order' => $order,
            'barcode' => $barcode,
            'qrCode' => $qrCode,
            'company' => [
                'name' => 'Boxleo Courier & Fulfillment Services Ltd',
                'phone' => '0761 976 581/0764 900539',
                'email' => 'tanzania@boxleocourier.com',
                'address' => 'Makongo juu Darajani, University Rd, Dar es Salaam'
            ]
        ]);
    }

    /**
     * Bulk print waybills
     */
    public function bulkPrintWaybills(Request $request)
    {
        $orderIds = $request->input('order_ids', []);

        $orders = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->whereIn('id', $orderIds)
            ->whereNull('deleted_at')
            ->get();

        $generator = new BarcodeGeneratorHTML();
        $waybills = [];

        foreach ($orders as $order) {
            $waybills[] = [
                'order' => $order,
                'barcode' => $generator->getBarcode($order->order_no, $generator::TYPE_CODE_128),
                'qrCode' => QrCode::size(100)->generate($order->order_no)
            ];
        }

        $data = [
            'waybills' => $waybills,
            'company' => [
                'name' => 'Boxleo Courier & Fulfillment Services Ltd',
                'phone' => '0761 976 581/0764 900539',
                'email' => 'tanzania@boxleocourier.com',
                'address' => 'Makongo juu Darajani, University Rd, Dar es Salaam'
            ]
        ];

        $pdf = Pdf::loadView('waybill.bulk-template', $data)
            ->setPaper('a5', 'landscape');

        return $pdf->stream('bulk_waybills.pdf');
    }
}
// This service class provides methods to perform bulk actions on orders such as assigning a rider, assigning an agent, and updating the status of multiple orders.