<?php

namespace App\Services\Order;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Jobs\AdvantaSmsJob;
use App\Models\OrderAssignment;
use App\Models\OrderEvent;
use App\Models\OrderStatusTimestamp;
use App\Models\Status;
use App\Models\WaybillSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Picqer\Barcode\BarcodeGeneratorHTML;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\Cache;
use Picqer\Barcode\BarcodeGeneratorPNG;
// use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

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





    // public function updateStatus(array $orderIds, int $statusId, ?string $statusNotes = null): void
    // {
    //     $templateService = app(MessageTemplateService::class);

    //     // ✅ Map status IDs to template slugs
    //     $statusName = DB::table('statuses')
    //         ->where('id', $statusId)
    //         ->value('name');

    //     $statusTemplateMap = $statusName
    //         ? [$statusId => $statusName]
    //         : [];

    //     foreach ($orderIds as $orderId) {

    //         try {
    //             // ✅ Save status timestamp
    //             OrderStatusTimestamp::create([
    //                 'order_id' => $orderId,
    //                 'status_id' => $statusId,
    //                 'status_notes' => $statusNotes,
    //             ]);

    //             // ❌ Skip if no template mapped
    //             if (!isset($statusTemplateMap[$statusId])) {
    //                 Log::info("No template mapped for status", [
    //                     'status_id' => $statusId,
    //                     'order_id' => $orderId
    //                 ]);
    //                 continue;
    //             }

    //             // ✅ Load order with customer
    //             $order = Order::with('customer')->find($orderId);

    //             if (!$order) {
    //                 Log::warning("Order not found", ['order_id' => $orderId]);
    //                 continue;
    //             }

    //             // ❌ Skip if no customer phone
    //             $phone = $order->customer?->phone;

    //             if (!$phone) {
    //                 Log::warning("Customer phone missing", ['order_id' => $orderId]);
    //                 continue;
    //             }

    //             // ✅ Generate message using template + order_id
    //             $result = $templateService->generateMessage(
    //                 phone: $phone,
    //                 templateSlug: $statusTemplateMap[$statusId],
    //                 additionalData: [
    //                     'order_id' => $orderId
    //                 ]
    //             );

    //             $message = $result['message'];

    //             // ❌ Skip empty messages
    //             if (empty($message)) {
    //                 Log::warning("Generated message is empty", [
    //                     'order_id' => $orderId,
    //                     'status_id' => $statusId
    //                 ]);
    //                 continue;
    //             }

    //             // ❌ Skip if no merchant (user)
    //             // if (!$order->user_id) {
    //             //     Log::warning("Order has no user_id", ['order_id' => $orderId]);
    //             //     continue;
    //             // }

    //             // ✅ Dispatch SMS job (WITH order_id 🔥)
    //             AdvantaSmsJob::dispatch(
    //                 recipients: $phone,
    //                 messageContent: $message,
    //                 userId: $order->user_id,
    //                 // orderId: $orderId
    //             );

    //             Log::info("SMS job dispatched successfully", [
    //                 'order_id' => $orderId,
    //                 'status_id' => $statusId,
    //                 'phone' => $phone
    //             ]);
    //         } catch (\Throwable $e) {
    //             Log::error("Failed updating order status", [
    //                 'order_id' => $orderId,
    //                 'status_id' => $statusId,
    //                 'error' => $e->getMessage()
    //             ]);
    //         }
    //     }
    // }





    public function updateStatus(array $orderIds, int $statusId, ?string $statusNotes = null): void
    {
        $templateService = app(MessageTemplateService::class);
        $transitionService = app(\App\Services\StatusTransitionService::class);

        $newStatus = \App\Models\Status::findOrFail($statusId);

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::with('latestStatus.status')->findOrFail($orderId);

                // ─────────────────────────────
                // OBEY TRANSITION RULES
                // ─────────────────────────────
                $transitionService->apply(
                    $order,
                    $newStatus->name,
                    $statusNotes
                );

                // ─────────────────────────────
                // SMS NOTIFICATION
                // ─────────────────────────────
                $phone = $order->customer?->phone;

                if (!$phone) {
                    Log::warning("Customer phone missing", ['order_id' => $orderId]);
                    continue;
                }

                $result = $templateService->generateMessage(
                    phone: $phone,
                    templateSlug: $newStatus->name,
                    additionalData: ['order_id' => $orderId]
                );

                if (empty($result['message'])) {
                    Log::warning("Generated message is empty", [
                        'order_id'  => $orderId,
                        'status_id' => $statusId,
                    ]);
                    continue;
                }

                AdvantaSmsJob::dispatch(
                    recipients: $phone,
                    messageContent: $result['message'],
                    userId: $order->user_id,
                );
            } catch (\RuntimeException $e) {
                // Transition validation failure — log and skip, don't crash the batch
                Log::warning('StatusTransitionService: Invalid transition', [
                    'order_id'  => $orderId,
                    'status_id' => $statusId,
                    'reason'    => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                Log::error("Failed updating order status", [
                    'order_id'  => $orderId,
                    'status_id' => $statusId,
                    'error'     => $e->getMessage(),
                ]);
            }
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

    // /**
    //  * Print waybill PDF for multiple orders (bulk).
    //  */
    // public function printWaybills(array $ids)
    // {
    //     $orders = Order::with([
    //         'customer',
    //         'customer.city',
    //         'customer.zone',
    //         'orderItems.product',
    //         'vendor',
    //         'rider',
    //         'agent'
    //     ])
    //         ->whereIn('id', $ids)
    //         ->whereNull('deleted_at')
    //         ->get();

    //     if ($orders->isEmpty()) {
    //         throw new \Exception("No orders found for given IDs");
    //     }

    //     // Get waybill settings
    //     $company = WaybillSetting::first();

    //     // Handle company settings safely (same logic as single print)
    //     if (!$company) {
    //         $company = (object) [
    //             'name' => config('app.name', 'Your Company'),
    //             'phone' => '',
    //             'email' => '',
    //             'address' => '',
    //             'template_name' => 'Express Courier',
    //             'options' => (object) ['color' => 'blue', 'size' => 'A6'],
    //             'terms' => 'Standard terms and conditions apply.',
    //             'footer' => 'Terms & Conditions',
    //             'brand_color' => '#667eea'
    //         ];
    //     } else {
    //         if (isset($company->options)) {
    //             if (is_string($company->options)) {
    //                 try {
    //                     $company->options = json_decode($company->options);
    //                     if (json_last_error() !== JSON_ERROR_NONE) {
    //                         $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
    //                     }
    //                 } catch (\Exception $e) {
    //                     Log::error('Error decoding company options in printWaybills', ['error' => $e->getMessage()]);
    //                     $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
    //                 }
    //             } elseif (is_array($company->options)) {
    //                 $company->options = (object) $company->options;
    //             } elseif (!is_object($company->options)) {
    //                 $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
    //             }
    //         } else {
    //             $company->options = (object) ['color' => 'blue', 'size' => 'A6'];
    //         }

    //         if (!isset($company->brand_color)) {
    //             $company->brand_color = '#667eea';
    //         }
    //     }

    //     // Generate barcodes for each order
    //     $generator = new BarcodeGeneratorHTML();
    //     $waybills = [];
    //     // Log the orders for debugging
    //     Log::info('Bulk waybill orders:', ['orders' => $orders->toArray()]);
    //     foreach ($orders as $order) {
    //         $waybills[] = [
    //             'order' => $order,
    //             'barcode' => $generator->getBarcode(
    //                 $order->order_no,
    //                 $generator::TYPE_CODE_128,
    //                 2,
    //                 50
    //             ),
    //             'company' => $company
    //         ];
    //     }

    //     // Determine paper size
    //     $paperSize = 'a6';
    //     if (isset($company->options->size)) {
    //         $paperSize = strtolower($company->options->size);
    //     }



    //     // It will dump everything to Laravel log so you can see exactly what's wrong.

    //     $logoRel    = $company->logo_path ?? $company->logo ?? 'images/rushbin-logo.png';
    //     $logoRel    = ltrim($logoRel, '/');
    //     $logoAbs    = public_path($logoRel);
    //     $logoExists = file_exists($logoAbs);

    //     Log::debug('=== LOGO DEBUG ===', [
    //         'company->logo_path'   => $company->logo_path ?? 'NOT SET',
    //         'company->logo'        => $company->logo       ?? 'NOT SET',
    //         'resolved_relative'    => $logoRel,
    //         'resolved_absolute'    => $logoAbs,
    //         'file_exists'          => $logoExists ? 'YES ✅' : 'NO ❌',
    //         'public_path()'        => public_path(),
    //     ]);


    //     // Load Blade template with multiple orders
    //     $pdf = Pdf::loadView('waybill.bulk-template', [
    //         'waybills' => $waybills,
    //         'company' => $company
    //     ])
    //         ->setPaper($paperSize, 'portrait')
    //         ->setOptions([
    //             'dpi' => 150,
    //             'defaultFont' => 'sans-serif',
    //             'isHtml5ParserEnabled' => true,
    //             // 'isPhpEnabled' => false,
    //             'isPhpEnabled'         => true,   // ← was false, change to true
    //             'chroot'               => realpath(base_path('public')), // ← add this

    //             'margin-top' => 0,
    //             'margin-right' => 0,
    //             'margin-bottom' => 0,
    //             'margin-left' => 0,
    //             'enable-local-file-access' => true,
    //         ]);

    //     // ✅ use a fixed filename for bulk
    //     return $pdf->stream("bulk_waybills.pdf");
    // }



    public function printWaybills(array $ids): mixed
    {
        // Single query with all relations
        $orders = Order::with([
            'customer.city',
            'customer.zone',
            'orderItems.product',
            'vendor',
            'rider',
            'agent',
            'latestStatus.status', // Eager load latest status for each order
        ])
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        if ($orders->isEmpty()) {
            throw new \Exception("No orders found for given IDs");
        }

        // ✅ Fetch status IDs once from DB — cached for 24 hours
        $scheduledStatus = Cache::remember(
            'status_scheduled',
            now()->addHours(24),
            fn() => Status::where('name', 'Scheduled')->first()
        );

        $awaitingDispatchStatus = Cache::remember(
            'status_awaiting_dispatch',
            now()->addHours(24),
            fn() => Status::where('name', 'Awaiting Dispatch')->first()
        );

        if (!$scheduledStatus) {
            throw new \Exception("Required statuses (Scheduled / Awaiting Dispatch) not found in DB");
        }

        // ✅ Single pass: collect scheduled IDs + mutate status in memory
        $scheduledSet = [];
        foreach ($orders as $order) {
            if ($order->latestStatus && $order->latestStatus->status->name === $scheduledStatus->name) {
                $scheduledSet[$order->id] = true;
            }
        }

        if (!empty($scheduledSet)) {


            // ✅ Correct insert using status_id FK + status_category_id
            $timestamps = array_map(fn($id) => [
                'order_id'           => $id,
                'status_id'          => $awaitingDispatchStatus->id,
                'status_notes'       => 'printed via bulk waybill action',
            ], array_keys($scheduledSet));

            OrderStatusTimestamp::insert($timestamps);

            Log::info('Bulk waybill: Scheduled → Awaiting Dispatch', [
                'count'     => count($scheduledSet),
                'order_ids' => array_keys($scheduledSet),
                'status_id' => $awaitingDispatchStatus->id,
            ]);
        }

        // ✅ Cached company settings
        $company = Cache::remember(
            'waybill_settings',
            now()->addMinutes(60),
            fn() => WaybillSetting::first()
        );
        $company = $this->normalizeCompanyOptions($company);

        // ✅ Build waybills — O(1) lookup via hash set
        // $generator = new BarcodeGeneratorHTML();
        // $generator = new BarcodeGeneratorPNG();



      $generator = new BarcodeGeneratorPNG();

foreach ($orders as $order) {
    $barcodePng = $generator->getBarcode(
        trim((string) $order->order_no),
        $generator::TYPE_CODE_128,
        3,   // widthFactor
        60   // height
    );

    // Convert to base64 data URI for Dompdf
    $barcode = 'data:image/png;base64,' . base64_encode($barcodePng);

    $waybills[] = [
        'order'          => $order,
        'barcode'        => $barcode,
        'company'        => $company,
        'status_changed' => isset($scheduledSet[$order->id]),
        'latest_status'  => $order->latestStatus,
    ];
}

        // ✅ Debug logging only in debug mode
        if (config('app.debug')) {
            Log::debug('=== LOGO DEBUG ===', [
                'logo' => ltrim($company->logo_path ?? $company->logo ?? 'images/rushbin-logo.png', '/'),
            ]);
        }

        $paperSize = strtolower($company->options->size ?? 'a6');

        return Pdf::loadView('waybill.bulk-template', compact('waybills', 'company'))
            ->setPaper($paperSize, 'portrait')
            ->setOptions([
                'dpi'                      => 150,
                'defaultFont'              => 'sans-serif',
                'isHtml5ParserEnabled'     => true,
                'isPhpEnabled'             => true,
                'chroot'                   => realpath(base_path('public')),
                'margin-top'               => 0,
                'margin-right'             => 0,
                'margin-bottom'            => 0,
                'margin-left'              => 0,
                'enable-local-file-access' => true,
            ])
            ->stream("bulk_waybills.pdf");
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


    /**
     * Normalize company options from WaybillSetting.
     */
    private function normalizeCompanyOptions(mixed $company): object
    {
        $default = (object) ['color' => 'blue', 'size' => 'A6'];

        if (!$company) {
            return (object) [
                'name'          => config('app.name', 'Your Company'),
                'phone'         => '',
                'email'         => '',
                'address'       => '',
                'template_name' => 'Express Courier',
                'options'       => $default,
                'terms'         => 'Standard terms and conditions apply.',
                'footer'        => 'Terms & Conditions',
                'brand_color'   => '#667eea',
            ];
        }

        if (!isset($company->options)) {
            $company->options = $default;
        } elseif (is_string($company->options)) {
            $decoded = json_decode($company->options);
            $company->options = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
        } elseif (is_array($company->options)) {
            $company->options = (object) $company->options;
        } elseif (!is_object($company->options)) {
            $company->options = $default;
        }

        $company->brand_color ??= '#667eea';

        return $company;
    }
}
