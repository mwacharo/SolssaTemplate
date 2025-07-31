<?php



namespace App\Services\Order;



use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Models\WaybillSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Illuminate\Support\Facades\Storage;



class OrderBulkActionService
{

    /**
     * Create a new order with provided data.
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create Order core fields
            $order = Order::create([
                'order_no' => $data['order_no'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'delivery_status' => $data['delivery_status'] ?? 'pending',
                'agent_id' => $data['agent_id'] ?? null,
                'rider_id' => $data['rider_id'] ?? null,
            ]);

            // Create client
            if (isset($data['client'])) {
                $order->client()->create($data['client']);
            }

            // Create order items
            if (isset($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $order->items()->create($itemData);
                }
            }

            return $order->load(['client', 'items', 'agent', 'rider']);
        });
    }






    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            // Update Order core fields
            $order->update([
                'order_no' => $data['order_no'] ?? $order->order_no,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
                'status' => $data['status'] ?? $order->status,
                'delivery_status' => $data['delivery_status'] ?? $order->delivery_status,
                'agent_id' => $data['agent_id'] ?? $order->agent_id,
                'rider_id' => $data['rider_id'] ?? $order->rider_id,
            ]);

            // Update client
            if (isset($data['client'])) {
                $order->client->update($data['client']);
            }

            // Update or create order items
            if (isset($data['items'])) {
                $existingIds = [];

                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update
                        $item = $order->items()->find($itemData['id']);
                        if ($item) {
                            $item->update($itemData);
                            $existingIds[] = $item->id;
                        }
                    } else {
                        // Create
                        $newItem = $order->items()->create($itemData);
                        $existingIds[] = $newItem->id;
                    }
                }

                // Delete removed items
                $order->items()->whereNotIn('id', $existingIds)->delete();
            }

            return $order->load(['client', 'items', 'agent', 'rider']);
        });
    }
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




public function printWaybill(Request $request, string $id)
{
    // Fetch order with all related data
    $order = Order::with([
        'client', 
        'orderItems.product', 
        'vendor', 
        'rider', 
        'agent'
    ])
    ->where('id', $id)
    ->whereNull('deleted_at')
    ->firstOrFail();

    // Generate barcode
    $generator = new BarcodeGeneratorHTML();
    $barcode = $generator->getBarcode(
        $order->order_no, 
        $generator::TYPE_CODE_128,
        2, // Width
        50 // Height
    );

    // Get waybill settings
    $company = WaybillSetting::first();

    // Debug: Log the company data structure
    Log::info('Company data structure:', [
        'company' => $company ? $company->toArray() : null,
        'options_type' => $company && isset($company->options) ? gettype($company->options) : 'null',
        'options_value' => $company && isset($company->options) ? $company->options : null
    ]);

    // Handle company settings safely
    if (!$company) {
        $company = (object) [
            'name' => config('app.name', 'Your Company'),
            'phone' => '',
            'email' => '',
            'address' => '',
            'template_name' => 'Express Courier',
            'options' => (object) ['color' => 'blue', 'size' => 'A6'],
            'terms' => 'Standard terms and conditions apply.',
            'footer' => 'Terms & Conditions',
            'brand_color' => '#667eea'
        ];
    } else {
        // Safely handle options field
        if (isset($company->options)) {
            if (is_string($company->options)) {
                try {
                    $company->options = json_decode($company->options);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
                    }
                } catch (\Exception $e) {
                    $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
                }
            } elseif (is_array($company->options)) {
                $company->options = (object) $company->options;
            } elseif (!is_object($company->options)) {
                $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
            }
        } else {
            $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
        }
        
        // Ensure brand_color exists
        if (!isset($company->brand_color)) {
            $company->brand_color = '#667eea';
        }
    }

    // Prepare data for the template
    $data = [
        'order' => $order,
        'barcode' => $barcode,
        'company' => $company
    ];

    // Determine paper size
    $paperSize = 'a6';
    if (isset($company->options->size)) {
        $paperSize = strtolower($company->options->size);
    }

    // Generate PDF
    $pdf = Pdf::loadView('waybill.template', $data)
        ->setPaper($paperSize, 'portrait')
        ->setOptions([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
            'enable-local-file-access' => true,
        ]);

    return $pdf->stream("waybill_{$order->order_no}.pdf");
}

    /**
     * Get waybill data for preview (without generating PDF)
     */
    public function getWaybillData(string $orderId)
    {
        $order = Order::with([
            'client', 
            'orderItems.product', 
            'vendor', 
            'rider', 
            'agent'
        ])
        ->where('id', $orderId)
        ->whereNull('deleted_at')
        ->firstOrFail();

        $generator = new BarcodeGeneratorHTML();
        $barcode = $generator->getBarcode($order->order_no, $generator::TYPE_CODE_128);

        $company = WaybillSetting::when($order->client && $order->client->country_id, function ($query) use ($order) {
                return $query->where('country_id', $order->client->country_id);
            })
            ->first() ?? WaybillSetting::first();

        return [
            'order' => $order,
            'barcode' => $barcode,
            'company' => $company
        ];
    }




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