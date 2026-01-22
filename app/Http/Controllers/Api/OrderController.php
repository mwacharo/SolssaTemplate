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
use App\Services\StockService;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


use Illuminate\Validation\ValidationException;




class OrderController extends Controller
{


    use AuthorizesRequests;





    public function __construct(

        protected OrderBulkActionService $orderService,

        protected StockService $stockService

    ) {

        // Apply middleware for API authentication/authorization
        // $this->middleware('auth:sanctum');
        // $this->middleware('can:view,App\Models\Order')->only(['index', 'show']);
        // $this->middleware('can:create,App\Models\Order')->only(['store']);
        // $this->middleware('can:update,App\Models\Order')->only(['update']);
        // $this->middleware('can:delete,App\Models\Order')->only(['destroy']);
    }

    public function timeline($id): JsonResponse
    {
        try {
            $timeline = $this->orderService->timeline($id);

            return response()->json([
                'success' => true,
                'data' => $timeline
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order timeline: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order timeline',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }



    public function assignRider(BulkOrderActionRequest $request)
    {
        Log::info('Assigning rider to orders', ['data' => $request->validated()]);
        $this->orderService->assignRider(
            $request->validated('order_ids'),
            $request->validated('rider_id'),
            // role 
            'Delivery Agent'
        );

        Log::info('Rider assigned successfully');
        return response()->json(['message' => 'Rider assigned successfully']);
    }

    public function assignAgent(BulkOrderActionRequest $request)
    {
        Log::info('Assigning agent to orders', ['data' => $request->validated()]);
        $this->orderService->assignAgent(
            $request->validated('order_ids'),
            $request->validated('agent_id'),
            // role
            'CallAgent'

        );
        Log::info('Agent assigned successfully');
        return response()->json(['message' => 'Agent assigned successfully']);
    }

    public function updateStatus(BulkOrderActionRequest $request)
    {
        Log::info('Updating order status', ['data' => $request->validated()]);
        $this->orderService->updateStatus(
            $request->validated('order_ids'),
            $request->validated('status')
        );
        Log::info('Order status updated successfully');
        return response()->json(['message' => 'Status updated successfully']);
    }


    // bulk delete orders
    public function bulkDelete(BulkOrderActionRequest $request)
    {
        Log::info('Bulk deleting orders', ['data' => $request->validated()]);
        $this->orderService->bulkDelete(
            $request->validated('order_ids')
        );
        Log::info('Orders deleted successfully');
        return response()->json(['message' => 'Orders deleted successfully']);
    }


    /**
     * Print waybill(s) for orders - Returns PDF stream
     */
    public function printWaybill(Request $request, string $id = null)
    {
        try {
            // Accept either a single ID from route or multiple from request
            $ids = $request->input('order_ids', $id ? [$id] : []);

            if (empty($ids)) {
                return response()->json([
                    'error' => 'No order IDs provided',
                ], 400);
            }

            // Generate the PDF with multiple orders
            $pdf = $this->orderService->printWaybills($ids);

            return $pdf;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate waybill(s)',
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
            $pdf = $this->orderService->printWaybills($request->input('order_ids'));

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
            // 'statusTimestamps' => function ($query) {
            //     $query->latest('created_at')->limit(1);
            // },
            'latestStatus.status.statusCategories', // ✅ latest status with relation

            // 'latestStatus.status', // ✅ latest status with relation


            'customer',
            'callLogs',
            // 'refunds',
            // 'remittances'
        ])
            ->where('id', $id)
            ->orWhere('order_no', $id)
            ->first();
        // ->find($id);

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

    // write function to fetch order journey from order_events table
    public function journey($id): JsonResponse
    {
        $order = Order::with(['events.user'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order->events
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, $id): JsonResponse
    {

        Log::info('Update order request received', [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'payload' => $request->all(),
        ]);
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Handle customer update or creation if customer data is provided
            // if (isset($validated['customer']) && is_array($validated['customer'])) {
            //     $customerData = $validated['customer'];
            //     $customer = Customer::firstOrCreate(
            //         ['phone' => $customerData['phone']],
            //         [
            //             'full_name' => $customerData['full_name'] ?? null,
            //             'email' => $customerData['email'] ?? null,
            //             'city_id' => $customerData['city_id'] ?? null,
            //             'zone_id' => $customerData['zone_id'] ?? null,
            //             'address' => $customerData['address'] ?? null,
            //             'region' => $customerData['region'] ?? null,
            //             'zipcode' => $customerData['zipcode'] ?? null,
            //         ]
            //     );
            //     $validated['customer_id'] = $customer->id;
            //     unset($validated['customer']);
            // }


            if (isset($validated['customer']) && is_array($validated['customer'])) {
                $customerData = $validated['customer'];

                // Find existing customer or create new one
                $customer = Customer::firstOrCreate(
                    ['phone' => $customerData['phone']]
                );

                // Update fields
                $customer->update([
                    'full_name' => $customerData['full_name'] ?? $customer->full_name,
                    'email' => $customerData['email'] ?? $customer->email,
                    'city_id' => $customerData['city_id'] ?? $customer->city_id,
                    'zone_id' => $customerData['zone_id'] ?? $customer->zone_id,
                    'address' => $customerData['address'] ?? $customer->address,
                    'region' => $customerData['region'] ?? $customer->region,
                    'zipcode' => $customerData['zipcode'] ?? $customer->zipcode,
                ]);

                $validated['customer_id'] = $customer->id;
                unset($validated['customer']);
            }


            // Update the order fields except order_items
            $order->update(collect($validated)->except(['order_items'])->toArray());

            // Update order items if provided
            if (isset($validated['order_items']) && is_array($validated['order_items'])) {
                // Delete existing items
                OrderItem::where('order_id', $order->id)->delete();

                // Create new items
                foreach ($validated['order_items'] as $item) {
                    $itemData = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'] ?? null,
                        'sku' => $item['sku'] ?? null,
                        'name' => $item['name'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $item['total_price'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'currency' => $item['currency'] ?? 'KSH',
                        'weight' => $item['weight'] ?? null,
                        'delivered_quantity' => $item['delivered_quantity'] ?? 0,
                    ];

                    // Try to resolve product_id by SKU if not provided
                    if (empty($itemData['product_id']) && !empty($itemData['sku'])) {
                        $product = Product::where('sku', $itemData['sku'])->first();
                        if ($product) {
                            $itemData['product_id'] = $product->id;
                        }
                    }

                    OrderItem::create($itemData);
                }
            }

            // Update status timestamp if status_id changed
            if (isset($validated['status_id']) && $validated['status_id'] != $order->getOriginal('status_id')) {
                OrderStatusTimestamp::create([
                    'order_id' => $order->id,
                    'status_id' => $validated['status_id'],
                    'status_category_id' => $validated['status_category_id'] ?? null,
                    'status_notes' => $validated['status_notes'] ?? null,
                    'created_at' => now()
                ]);
            }

            $order->events()->create([
                'event_type' => 'order_updated',
                'event_data' => json_encode($validated),
                'user_id'    => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => $order->load([
                    'orderItems.product',
                    'addresses',
                    'statusTimestamps',
                    'customer',
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









    private function applyFilters($query, Request $request): void
    {
        // Vendor filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Status filter with optional date range
        // KEY FIX: Combined logic to prevent double filtering
        if ($request->filled('status') && ($request->filled('status_from') || $request->filled('status_to'))) {
            // Orders that were set to this status within the date range
            $query->whereHas('statusTimestamps', function ($q) use ($request) {
                $q->where('status_id', $request->status);

                if ($request->filled('status_from')) {
                    $q->whereDate('created_at', '>=', $request->status_from);
                }
                if ($request->filled('status_to')) {
                    $q->whereDate('created_at', '<=', $request->status_to);
                }
            });
        } elseif ($request->filled('status')) {
            // Orders currently in this status (no date filter)
            $query->whereHas('latestStatus', function ($q) use ($request) {
                $q->where('status_id', $request->status);
            });
        } elseif ($request->filled('status_from') || $request->filled('status_to')) {
            // Orders where ANY status was set within date range
            $query->whereHas('statusTimestamps', function ($q) use ($request) {
                if ($request->filled('status_from')) {
                    $q->whereDate('created_at', '>=', $request->status_from);
                }
                if ($request->filled('status_to')) {
                    $q->whereDate('created_at', '<=', $request->status_to);
                }
            });
        }

        // Delivery date filters
        if ($request->filled('delivery_from')) {
            $query->whereDate('delivery_date', '>=', $request->delivery_from);
        }
        if ($request->filled('delivery_to')) {
            $query->whereDate('delivery_date', '<=', $request->delivery_to);
        }
        // Fallback single delivery_date
        if ($request->filled('delivery_date') && !$request->filled('delivery_from')) {
            $query->whereDate('delivery_date', '>=', $request->delivery_date);
        }

        // Order created date range
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Alternative date filters (aliases)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Agent filters
        if ($request->filled('agent_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('user_id', $request->agent_id)
                    ->whereIn('role', ['CallAgent', 'Call Agent', 'call_agent']);
            });
        }

        if ($request->filled('rider_id')) {
            $query->whereHas('assignments', function ($q) use ($request) {
                $q->where('user_id', $request->rider_id)
                    ->whereIn('role', ['Delivery Agent', 'Delivery Man', 'DeliveryAgent']);
            });
        }

        // Customer location filters
        if ($request->filled('city_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
        }

        if ($request->filled('zone_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('zone_id', $request->zone_id);
            });
        }
    }


    /**
     * Apply search to the query
     */
    private function applySearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('order_no', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('orderItems.product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Apply additional filters for vendor_id, city, category_id, created_from, created_to
     */
    private function applyExtraFilters($query, Request $request): void
    {
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->get('vendor_id'));
        }

        if ($request->filled('city')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('city', $request->get('city'));
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->get('category_id');
            $query->whereHas('orderItems.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }
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







//     /**
//      * Display a listing of orders.
//      */
//     public function index(Request $request): JsonResponse
//     {

//           // 1. Apply policy
//     // $this->authorize('viewAny', Order::class);

//     $user = $request->user();
//     $perPage = $request->input('per_page', 15);

//         $query = Order::with([
//             'warehouse',
//             'country',
//             'agent',
//             'createdBy',
//             'rider',
//             'zone',
//             'orderItems',
//             'addresses',
//             'shippingAddress',
//             'pickupAddress',
//             'assignments.user',
//             'payments',
//             'latestStatus.status',
//             'customer',
//             'vendor'
//         ]);


//    $userRole = $user->role;




//         // Apply filters
//         $this->applyFilters($query, $request);

//         // Filter by product_id if provided
//         if ($request->filled('product_id')) {
//             $productId = $request->input('product_id');
//             $query->whereHas('orderItems', function ($q) use ($productId) {
//                 $q->where('product_id', $productId);
//             });
//         }

//         // Apply search if provided
//         if ($request->filled('search')) {
//             $this->applySearch($query, $request->input('search'));
//         }

//         // Apply sorting
//         $this->applySorting($query, $request);

//         $orders = $query->paginate($perPage);

//         return response()->json([
//             'success' => true,
//             'data' => $orders
//         ]);
//     }


    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {

        Log::info('Fetching orders with filters', ['filters' => $request->all()]);
        // 1. Apply policy (uncomment when ready)
        // $this->authorize('viewAny', Order::class);

        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        // Build base query with relationships
        $query = Order::with([
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
            'latestStatus.status',
            'statusTimestamps.status',
            // 'customer',
            'customer.city',      // Add this
            'customer.zone',      // Add this

            'vendor',
            'callLogs',
        ]);

        // Get user role
        // Get user role using Spatie (supports multiple roles)
        $userRole = $user ? $user->getRoleNames()->first() : null;

        // Debug logs (remove in production)
        \Log::info('Current User Role: ' . $userRole);
        \Log::info('Current User ID: ' . $user->id);

        // Apply role-based filtering
        if (in_array($userRole, ['Delivery Agent', 'Delivery Man', 'DeliveryAgent'])) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('role', ['Delivery Agent', 'Delivery Man', 'DeliveryAgent']);
            });
            \Log::info('Applied Delivery Agent filter');
        } elseif (in_array($userRole, ['CallAgent', 'Call Agent', 'call_agent'])) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('role', ['CallAgent', 'Call Agent', 'call_agent']);
            });
            \Log::info('Applied Call Agent filter');
        }
        // If role is Admin, Vendor, or other - show all orders (no filter applied)

        // Apply additional filters
        $this->applyFilters($query, $request);

        // Filter by product_id if provided
        if ($request->filled('product_id')) {
            $productId = $request->input('product_id');
            $query->whereHas('orderItems', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        // Apply search if provided
        if ($request->filled('search')) {
            $this->applySearch($query, $request->input('search'));
        }

        // Apply sorting
        $this->applySorting($query, $request);

        // Debug: Log the SQL query
        \Log::info('Generated SQL: ' . $query->toSql());
        \Log::info('Query Bindings: ' . json_encode($query->getBindings()));

        // Paginate results
        $orders = $query->paginate($perPage);

        // Debug: Log result count
        \Log::info('Orders returned: ' . $orders->count());

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

            // log auth user details
            Log::info('Auth user details', ['user' => Auth::user()]);

            // $user = Auth::user();
            // if ($user && $user->hasRole('Vendor')) {
            //     $validated['vendor_id'] = $user->id;
            // } else {
            //     if (empty($validated['vendor_id'])) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Vendor ID is required'
            //         ], 422);
            //     }
            //     $validated['vendor_id'] = $validated['vendor_id'] ?? null;
            // }



            // ✅ Get authenticated user from Bearer token
            $user = Auth::user();
            Log::info('Authenticated user', ['user' => $user]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'allowed_origins. Please provide a valid vendor token.'
                ], 401);
            }

            // ✅ Check if user is a vendor
            if ($user->hasRole('Vendor')) {
                $validated['vendor_id'] = $user->id;
                Log::info('Vendor identified from Bearer token', ['vendor_id' => $user->id]);
            } 
            
            // else {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'User is not authorized as a vendor'
            //     ], 403);
            // }


            /**
             * Step 1: Handle customer
             * If pickup_address and dropoff_address exist, treat both as customers and create them.
             */
            $customerId = $validated['customer_id'] ?? null;

            // If both pickup and dropoff addresses exist, create customers for both
            if (!empty($validated['pickup_address']) && !empty($validated['dropoff_address'])) {
                // Create pickup customer
                $pickupCustomer = Customer::firstOrCreate(
                    ['phone' => $validated['pickup_address']['phone']],
                    [
                        'name' => $validated['pickup_address']['full_name'] ?? null,
                        'email' => $validated['pickup_address']['email'] ?? null,
                        'city_id' => $validated['pickup_address']['city_id'] ?? null,

                        'zone_id' => $validated['pickup_address']['zone_id'] ?? null,
                        'address' => $validated['pickup_address']['address'] ?? null,
                        'region' => $validated['pickup_address']['region'] ?? null,
                        'zipcode' => $validated['pickup_address']['zipcode'] ?? null,
                    ]
                );
                // Create dropoff customer
                $dropoffCustomer = Customer::firstOrCreate(
                    ['phone' => $validated['dropoff_address']['phone']],
                    [
                        'name' => $validated['dropoff_address']['full_name'] ?? null,
                        'email' => $validated['dropoff_address']['email'] ?? null,
                        'city_id' => $validated['pickup_address']['city_id'] ?? null,

                        'zone_id' => $validated['dropoff_address']['zone_id'] ?? null,
                        'address' => $validated['dropoff_address']['address'] ?? null,
                        'region' => $validated['dropoff_address']['region'] ?? null,
                        'zipcode' => $validated['dropoff_address']['zipcode'] ?? null,
                    ]
                );
                // Set main customer_id as pickup customer
                $customerId = $pickupCustomer->id;
                // Optionally, you can store dropoff_customer_id in the order if your schema supports it
                $validated['dropoff_customer_id'] = $dropoffCustomer->id;
            } elseif (!$customerId && !empty($validated['customer']['phone'])) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $validated['customer']['phone']],
                    $validated['customer']
                );
                $customerId = $customer->id;
            }

            $validated['customer_id'] = $customerId ?? null;
            unset($validated['customer']);


            // ✅ REMOVE NESTED DATA BEFORE CREATING ORDER
            unset($validated['customer']);
            unset($validated['pickup_address']);
            unset($validated['dropoff_address']);
            unset($validated['from_address']);
            unset($validated['to_address']);
            // unset($validated['order_items']);
            unset($validated['addresses']);

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
                        $product = Product::where('sku', $item['sku'])->firstOrFail();
                        $item['product_id'] = $product->id;

                        $orderItem = $order->orderItems()->create($item);
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
                'status_id'     => $order->status_id ?? 1,
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
        DB::beginTransaction();

        try {
            $order = Order::with([
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
                // 'statusTimestamps' => function ($query) {
                //     $query->latest('created_at')->limit(1);
                // },
                // 'statusTimestamps.status',
                'latestStatus.status',
                'customer'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Delete related details
            $order->orderItems()->delete();
            $order->addresses()->delete();
            $order->shippingAddress()->delete();
            $order->pickupAddress()->delete();
            $order->assignments()->delete();
            $order->payments()->delete();
            $order->events()->delete();
            $order->statusTimestamps()->delete();

            // Delete the order itself
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order and all its details deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // find by assignemnt  person /rider /call agnet /vendor / courier  etc 

    //     Route::get('/orders/find-by/{type}/{identifier}', [OrderController::class, 'findByIdentifier']);


    public function findByIdentifier(Request $request, $type, $identifier): JsonResponse
    {
        try {
            $query = Order::with([
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
                'latestStatus.status',
                'customer',
                'vendor'
            ]);

            switch (strtolower($type)) {
                case 'rider':
                    $query->whereHas('assignments', function ($q) use ($identifier) {
                        $q->where('user_id', $identifier)
                            ->whereIn('role', ['Delivery Agent', 'Delivery    Man', 'DeliveryAgent']);
                    });
                    break;
                case 'call_agent':
                case 'call_agent':
                    $query->whereHas('assignments', function ($q) use ($identifier) {
                        $q->where('user_id', $identifier)
                            ->whereIn('role', ['Call Agent', 'CallCenterAgent']);
                    });
                    break;
            }

            $orders = $query->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function getByAgent(Request $request, $agentId): JsonResponse
    {
        Log::info('getByAgent called', [
            'agent_id' => $agentId,
            'input' => $request->all(),
            'user_id' => $request->user()?->id
        ]);

        try {
            $validated = $request->validate([
                'from' => 'nullable|date',
                'to'   => 'nullable|date|after_or_equal:from',
            ]);

            Log::debug('getByAgent validated payload', ['validated' => $validated]);

            $query = Order::with([
                'warehouse',
                'agent',
                'rider',
                'zone',
                'orderItems.product',
                'customer',
                'vendor',
                'latestStatus.status',
                'shippingAddress'
            ])
                ->whereHas('assignments', function ($q) use ($agentId) {
                    $q->where('user_id', $agentId)
                        ->whereIn('role', ['CallAgent', 'CallCenterAgent']);
                })
                /**
                 * 🔑 ONLY orders whose LATEST status is Delivered
                 * 🔑 AND that status happened between from & to
                 */
                ->whereHas('latestStatus', function ($q) use ($validated) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('name', 'Delivered'); // 🚫 no slug
                    });

                    if (!empty($validated['from'])) {
                        $q->whereDate('created_at', '>=', $validated['from']);
                    }

                    if (!empty($validated['to'])) {
                        $q->whereDate('created_at', '<=', $validated['to']);
                    }
                });

            // Log SQL and bindings for debugging (note: closures may prevent full SQL preview)
            try {
                Log::debug('getByAgent query SQL', [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            } catch (\Exception $e) {
                Log::debug('Could not dump query SQL', ['error' => $e->getMessage()]);
            }

            Log::info('Executing getByAgent query', ['agent_id' => $agentId]);
            $orders = $query->orderBy('created_at', 'desc')->get();

            Log::info('getByAgent query executed', [
                'agent_id' => $agentId,
                'result_count' => $orders->count(),
                'orders' => $orders

            ]);

            return response()->json([
                'success' => true,
                'data'    => $orders,
                'count'   => $orders->count(),
            ]);
        } catch (ValidationException $e) {
            Log::warning('getByAgent validation failed', [
                'agent_id' => $agentId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch agent delivered orders', [
                'agent_id' => $agentId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Get orders by rider
     */
    public function getByRider(Request $request, $riderId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
            ]);

            $query = Order::with([
                'warehouse',
                'rider',
                'zone',
                'orderItems.product',
                'customer',
                'vendor',
                'latestStatus.status',
                'shippingAddress'
            ])
                ->whereHas('assignments', function ($q) use ($riderId) {
                    $q->where('user_id', $riderId)
                        ->whereIn('role', ['Delivery Agent', 'Delivery Man', 'DeliveryAgent']);
                });

            // Apply date filters
            if (!empty($validated['from'])) {
                $query->whereDate('created_at', '>=', $validated['from']);
            }

            if (!empty($validated['to'])) {
                $query->whereDate('created_at', '<=', $validated['to']);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => $orders->count()
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch rider orders', [
                'rider_id' => $riderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
