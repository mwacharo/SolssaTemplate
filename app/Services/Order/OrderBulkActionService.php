<?php

namespace App\Services\Order;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Models\OrderAssignment;
use App\Models\OrderEvent;
use App\Models\OrderStatusTimestamp;
use App\Models\WaybillSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;



class OrderBulkActionService
{

    // 

    public function timeline($id): JsonResponse
    {
        $order = Order::with(['events.user', 'statusTimestamps.status'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Merge events and status timestamps into a single timeline array
        $timeline = [];

        foreach ($order->statusTimestamps as $statusTimestamp) {
            $timeline[] = [
                'type' => 'status',
                'status' => $statusTimestamp->status->name ?? null,
                'status_id' => $statusTimestamp->status_id,
                'created_at' => $statusTimestamp->created_at,
            ];
        }

        foreach ($order->events as $event) {
            $timeline[] = [
                'type' => 'event',
                'event_type' => $event->event_type,
                'event_data' => $event->event_data,
                'user' => $event->user ? [
                    'id' => $event->user->id,
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                ] : null,
                'created_at' => $event->created_at,
            ];
        }

        // Sort timeline by created_at ascending
        usort($timeline, function ($a, $b) {
            return strtotime($a['created_at']) <=> strtotime($b['created_at']);
        });

        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    }



    public function logEvent($orderId, string $eventType, array $eventData = [])
    {
        try {
            OrderEvent::create([
                'order_id'   => $orderId,
                'event_type' => $eventType,
                'event_data' => $eventData,
                'actor_id'   => auth()->id(),
            ]);

            Log::info("Order event logged: {$eventType}", ['order_id' => $orderId, 'data' => $eventData]);
        } catch (\Throwable $e) {
            Log::error("Failed to log order event", ['error' => $e->getMessage()]);
        }
    }


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
            if (isset($data['client']) && $order->client) {
                $order->client->update($data['client']);
            }

            // Update or create order items using orderItems() relationship
            if (isset($data['items'])) {
                $existingIds = [];

                foreach ($data['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update
                        $item = $order->orderItems()->find($itemData['id']);
                        if ($item) {
                            $item->update($itemData);
                            $existingIds[] = $item->id;
                        }
                    } else {
                        // Create
                        $newItem = $order->orderItems()->create($itemData);
                        $existingIds[] = $newItem->id;
                    }
                }

                // Delete removed items
                $order->orderItems()->whereNotIn('id', $existingIds)->delete();
            }

            return $order->load(['client', 'orderItems', 'agent', 'rider']);
        });
    }
    public function assignRider(array $orderIds, int $riderId, string $role = 'Delivery Agent'): void
    {
        foreach ($orderIds as $orderId) {
            // Create or update assignment record for this order and rider
            OrderAssignment::updateOrCreate(
                [
                    'order_id' => $orderId,
                    'user_id' => $riderId,
                    'role' => $role,
                ],
                [
                    // 'assigned_by' => $assignedBy,
                    // 'assigned_at' => now(),
                    // 'active' => 1,
                ]
            );
        }
    }

    public function assignAgent(array $orderIds, int $agentId, string $role = 'CallAgent'): void
    {
        foreach ($orderIds as $orderId) {
            // Create or update assignment record for this order and agent
            OrderAssignment::updateOrCreate(
                [
                    'order_id' => $orderId,
                    'user_id' => $agentId,
                    'role' => $role,
                ],
                [
                    // 'assigned_by' => $assignedBy,
                    // 'assigned_at' => now(),
                    // 'active' => 1,
                ]
            );
        }
    }

    public function updateStatus(array $orderIds, $statusId, $statusNotes = null): void
    {
        foreach ($orderIds as $orderId) {
            OrderStatusTimestamp::create([
                'order_id' => $orderId,
                'status_id' => $statusId,
                'status_notes' => $statusNotes,

            ]);
        }
    }


    // bulk delete orders

    public function bulkDelete(array $orderIds): int
    {
        // Soft delete orders by IDs
        $deletedCount = Order::whereIn('id', $orderIds)->delete();

        return $deletedCount;
    }



    /**
     * Print waybill PDF for a single order.
     */
    public function printWaybill(Request $request, string $id)
    {
        // Fetch order with all related data
        $order = Order::with([
            'customer',
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
                        Log::error('Error decoding company options in printWaybills', ['error' => $e->getMessage()]);
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
     * Print waybill PDF for multiple orders (bulk).
     */
    public function printWaybills(array $ids)
    {
        $orders = Order::with([
            'customer',
            'customer.city',
            'customer.zone',
            'orderItems.product',
            'vendor',
            'rider',
            'agent'
        ])
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        if ($orders->isEmpty()) {
            throw new \Exception("No orders found for given IDs");
        }

        // Get waybill settings
        $company = WaybillSetting::first();

        // Handle company settings safely (same logic as single print)
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
            if (isset($company->options)) {
                if (is_string($company->options)) {
                    try {
                        $company->options = json_decode($company->options);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
                        }
                    } catch (\Exception $e) {
                        Log::error('Error decoding company options in printWaybills', ['error' => $e->getMessage()]);
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

            if (!isset($company->brand_color)) {
                $company->brand_color = '#667eea';
            }
        }

        // Generate barcodes for each order
        $generator = new BarcodeGeneratorHTML();
        $waybills = [];
        // Log the orders for debugging
        Log::info('Bulk waybill orders:', ['orders' => $orders->toArray()]);
        foreach ($orders as $order) {
            $waybills[] = [
                'order' => $order,
                'barcode' => $generator->getBarcode(
                    $order->order_no,
                    $generator::TYPE_CODE_128,
                    2,
                    50
                ),
                'company' => $company
            ];
        }

        // Determine paper size
        $paperSize = 'a6';
        if (isset($company->options->size)) {
            $paperSize = strtolower($company->options->size);
        }

        // Load Blade template with multiple orders
        $pdf = Pdf::loadView('waybill.bulk-template', [
            'waybills' => $waybills,
            'company' => $company
        ])
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

        // ✅ use a fixed filename for bulk
        return $pdf->stream("bulk_waybills.pdf");
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
}
