<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BulkOrderActionRequest;
use App\Http\Requests\StoreOrderRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\OrderStatusTimestamp;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\Order\OrderBulkActionService;
use Illuminate\Auth\Events\Validated;
// app/Services/Order/OrderBulkActionService.php
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

    public function __construct(protected OrderBulkActionService $orderService)
    {




        // Apply middleware for API authentication/authorization
        // $this->middleware('auth:sanctum');
        // $this->middleware('can:view,App\Models\Order')->only(['index', 'show']);
        // $this->middleware('can:create,App\Models\Order')->only(['store']);
        // $this->middleware('can:update,App\Models\Order')->only(['update']);
        // $this->middleware('can:delete,App\Models\Order')->only(['destroy']);
    }



    public function assignRider(BulkOrderActionRequest $request)
    {
        Log::info('Assigning rider to orders', ['data' => $request->validated()]);
        $this->orderService->assignRider(
            $request->validated('order_ids'),
            $request->validated('rider_id')
        );

        Log::info('Rider assigned successfully');
        return response()->json(['message' => 'Rider assigned successfully']);
    }

    public function assignAgent(BulkOrderActionRequest $request)
    {
        Log::info('Assigning agent to orders', ['data' => $request->validated()]);
        $this->orderService->assignAgent(
            $request->validated('order_ids'),
            $request->validated('agent_id')
        );
        Log::info('Agent assigned successfully');
        return response()->json(['message' => 'Agent assigned successfully']);
    }

    public function updateStatus(BulkOrderActionRequest $request)
    {
        Log::info('Updating order status', ['data' => $request->validated()]);
        $this->orderService->updateStatus(
            $request->validated('orderIds'),
            $request->validated('status')
        );
        Log::info('Order status updated successfully');
        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Print waybill for an order - Returns PDF stream
     */
    public function printWaybill(Request $request, string $id)
    {
        try {
            // Call the service to get the PDF
            $pdf = $this->orderService->printWaybill($request, $id);

            // Return the PDF stream
            return $pdf;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate waybill',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // bulkprint waybill

    public function bulkPrintWaybill(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'order_ids' => 'required|array',
                'order_ids.*' => 'exists:orders,id',
            ]);

            // Call the service to get the PDF
            $pdf = $this->orderService->bulkPrintWaybills($request->input('order_ids'));

            // Return the PDF stream
            return $pdf;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate bulk waybill',
                'message' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * Display the specified order.
     */
    public function show($id): JsonResponse
    {
        $order = Order::with([
            'warehouse',
            'country',
            'agent',
            'createdBy',
            'rider',
            'zone',
            'orderItems.product',
            'addresses',
            'shippingAddress',
            'pickupAddress',
            'assignments.user',
            'payments',
            // 'events.user',
            'statusTimestamps',
            // 'refunds',
            // 'remittances'
        ])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Update the order
            $order->update($request->validated());

            // Update order items if provided
            if ($request->has('order_items')) {
                // Delete existing items
                OrderItem::where('order_id', $order->id)->delete();

                // Create new items
                foreach ($request->input('order_items') as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }
            }

            // Update status timestamp if status changed
            if ($request->has('status') && $request->input('status') !== $order->getOriginal('status')) {
                OrderStatusTimestamp::create([
                    'order_id' => $order->id,
                    'status' => $request->input('status'),
                    'created_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order->load([
                    'orderItems',
                    'addresses',
                    'statusTimestamps'
                ])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
















    public function restore(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $order = Order::withTrashed()->findOrFail($id);

            if (!$order->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is not deleted'
                ], 422);
            }

            $order->restore();

            Log::info('Order restored successfully', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order restored successfully',
                'data' => new OrderResource($order)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error restoring order: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore order',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk operations on orders
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:assign_rider,assign_agent,update_status,delete',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'value' => 'required_unless:action,delete'
        ]);

        try {
            DB::beginTransaction();

            $orderIds = $request->get('order_ids');
            $action = $request->get('action');
            $value = $request->get('value');

            switch ($action) {
                case 'assign_rider':
                    $this->orderService->assignRider($orderIds, $value);
                    $message = 'Rider assigned successfully to selected orders';
                    break;

                case 'assign_agent':
                    $this->orderService->assignAgent($orderIds, $value);
                    $message = 'Agent assigned successfully to selected orders';
                    break;

                case 'update_status':
                    $this->orderService->updateStatus($orderIds, $value);
                    $message = 'Status updated successfully for selected orders';
                    break;

                case 'delete':
                    Order::whereIn('id', $orderIds)->update(['deleted_at' => now()]);
                    $message = 'Selected orders deleted successfully';
                    break;
            }

            Log::info('Bulk action performed', [
                'action' => $action,
                'order_ids' => $orderIds,
                'value' => $value,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_orders' => count($orderIds)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing bulk action: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk action',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Order::whereNull('deleted_at');

            // Apply date filter if provided
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
            }

            $stats = [
                'total_orders' => $query->count(),
                'status_breakdown' => $query->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'delivery_status_breakdown' => $query->selectRaw('delivery_status, COUNT(*) as count')
                    ->groupBy('delivery_status')
                    ->pluck('count', 'delivery_status'),
                'total_revenue' => $query->sum('total_amount') ?? 0,
                'average_order_value' => $query->avg('total_amount') ?? 0,
                'orders_by_date' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->take(30)
                    ->pluck('count', 'date')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order statistics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->get('agent_id'));
        }

        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->get('rider_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        if ($request->filled('delivery_date_from')) {
            $query->whereDate('delivery_date', '>=', $request->get('delivery_date_from'));
        }

        if ($request->filled('delivery_date_to')) {
            $query->whereDate('delivery_date', '<=', $request->get('delivery_date_to'));
        }
    }

    /**
     * Apply search to the query
     */
    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('order_no', 'like', "%{$search}%")
                ->orWhereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('orderItems.product', function ($productQuery) use ($search) {
                    $productQuery->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Validate sort field
        $allowedSortFields = [
            'created_at',
            'updated_at',
            'order_no',
            'status',
            'delivery_status',
            'delivery_date',
            'total_amount'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest();
        }
    }

    /**
     * Generate a unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (int) substr($lastOrder->order_no, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if order can be deleted
     */
    private function canDeleteOrder(Order $order): bool
    {
        $nonDeletableStatuses = ['processing', 'completed', 'delivered'];
        return !in_array($order->status, $nonDeletableStatuses);
    }







    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $orders = Order::with([
            'warehouse',
            'country',
            'agent',
            'createdBy',
            'rider',
            'zone',
            'orderItems',
            'addresses',
            'shippingAddress',
            'pickupAddress',
            'assignments',
            'payments',
            // 'events',
            'statusTimestamps.status'
        ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }


    public function store(StoreOrderRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            Log::info('Validated order data', ['validated' => $validated]);


            // set zero 

            // check if auth hasrole vendor  take vendor id from auth


            // log auth user details
            Log::info('Auth user details', ['user' => Auth::user()]);

            $user = Auth::user();
            if ($user && $user->hasRole('Vendor')) {
                $validated['vendor_id'] = $user->id;
            } else {
                // vendor is required therefore user must provide vendor id
                if (empty($validated['vendor_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vendor ID is required'
                    ], 422);
                }
                $validated['vendor_id'] = $validated['vendor_id'] ?? null;
            }

            /**
             * Step 1: Handle customer
             */
            $customerId = $validated['customer_id'] ?? null;

            if (!$customerId && !empty($validated['customer']['phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $validated['customer']['phone']],
                    $validated['customer']
                );
                $customerId = $customer->id;
            }

            $validated['customer_id'] = $customerId ?? null;
            unset($validated['customer']);

            /**
             * Step 2: Check duplicate order number
             */
            if (!empty($validated['order_no'])) {
                $existingOrder = Order::where('order_no', $validated['order_no'])->first();
                if ($existingOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order number already exists'
                    ], 422);
                }
            }

            /**
             * Step 3: Create Order
             */
            $order = Order::create($validated);
            Log::info('Order created', ['order_id' => $order->id]);

            /**
             * Step 4: Create Order Items + Inventory Reservation
             */
            if (!empty($validated['order_items'])) {
                foreach ($validated['order_items'] as $item) {
                    try {
                        // Find product by SKU instead of product_id
                        $product = Product::where('sku', $item['sku'])->firstOrFail();
                        $item['product_id'] = $product->id;

                        $orderItem = $order->orderItems()->create($item);

                        // Reserve stock using SKU
                        InventoryReservation::create([
                            'sku'        => $item['sku'],
                            'order_id'   => $order->id,
                            'quantity'   => $item['quantity'],
                            'reserved_at' => now(),
                            'reason'     => 'Order created',
                        ]);
                    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                        Log::error('Product not found for order item', [
                            'sku' => $item['sku'],
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                            'item' => $item
                        ]);
                        throw $e;
                    } catch (\Exception $e) {
                        Log::error('Unexpected error while creating order item', [
                            'sku' => $item['sku'] ?? null,
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                            'item' => $item
                        ]);
                        throw $e;
                    }
                }
            }

            /**
             * Step 5: Create Addresses
             */
            if (!empty($validated['addresses'])) {
                foreach ($validated['addresses'] as $address) {
                    $order->addresses()->create($address);
                }
            }

            /**
             * Step 6: Create Payment Record (if paid)
             */
            if (!empty($validated['paid']) && $validated['paid'] === true) {
                $payment = $order->payments()->create([
                    'amount'       => $validated['total_price'],
                    'method'       => $validated['payment_method'] ?? 'unknown',
                    'status'       => 'completed',
                    'transaction_reference' => $validated['payment_id'] ?? null,
                    'currency'     => $validated['currency'] ?? 'USD',
                    'user_id'      => Auth::id(),
                ]);
                Log::info('Payment recorded', ['payment_id' => $payment->id]);
            }

            /**
             * Step 7: Log Status Timestamp
             */
            OrderStatusTimestamp::create([
                'order_id'   => $order->id,
                'status_id'     => $order->status_id ?? 1, // Default to 1 if not set
                'created_at' => now()
            ]);

            /**
             * Step 8: Log Event
             */
            $order->events()->create([
                'event_type' => 'order_created',
                'event_data' => json_encode($validated),
                'user_id'    => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data'    => $order->load([
                    'orderItems',
                    'addresses',
                    'statusTimestamps',
                    'events',
                    'customer',
                    'payments',
                ])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }
}
