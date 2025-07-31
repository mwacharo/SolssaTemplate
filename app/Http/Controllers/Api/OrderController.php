<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BulkOrderActionRequest;
use App\Http\Requests\StoreOrderRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\Order\OrderBulkActionService;
use Illuminate\Auth\Events\Validated;
// app/Services/Order/OrderBulkActionService.php
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function __construct(protected OrderBulkActionService $orderService) {




        // Apply middleware for API authentication/authorization
        // $this->middleware('auth:sanctum');
        // $this->middleware('can:view,App\Models\Order')->only(['index', 'show']);
        // $this->middleware('can:create,App\Models\Order')->only(['store']);
        // $this->middleware('can:update,App\Models\Order')->only(['update']);
        // $this->middleware('can:delete,App\Models\Order')->only(['destroy']);
    }


    

    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     // Eager load relationships and paginate
    //     $orders = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
    //         ->whereNull('deleted_at')
    //         ->latest()
    //         ->paginate(20);

    //     // Return resource collection with pagination structure
    //     return OrderResource::collection($orders);
    // }


    /**
     * Store a newly created resource in storage.
     */
    // public function store(StoreOrderRequest $request)
    // {
    //     // Validate and get the data
    //     $validated = $request->validated();

    //     // Create the order using the service
    //     $order = $this->orderService->createOrder($validated);

    //     // Return the newly created order as a resource
    //     return (new OrderResource($order))
    //         ->response()
    //         ->setStatusCode(201);
    // }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     // Eager load relationships for a single order
    //     $order = Order::with(['client', 'orderItems.product', 'vendor.products', 'rider', 'agent'])
    //         ->where('id', $id)
    //         ->whereNull('deleted_at')
    //         ->firstOrFail();

    //     // Return single order resource
    //     return new OrderResource($order);
    // }



    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }


    // public function update(UpdateOrderRequest $request, $id)
    // {
    //     $order = Order::with(['client', 'items', 'agent', 'rider'])->findOrFail($id);

    //     $updatedOrder = $this->orderService->updateOrder($order, $request->validated());

    //     return response()->json([
    //         'message' => 'Order updated successfully',
    //         'data' => $updatedOrder
    //     ]);
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(string $id)
    // {
    //     //
    // }

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
     * Display a listing of the resource with advanced filtering and searching
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
                ->whereNull('deleted_at');

            // Apply filters
            $this->applyFilters($query, $request);

            // Apply search
            if ($request->filled('search')) {
                $this->applySearch($query, $request->get('search'));
            }

            // Apply sorting
            $this->applySorting($query, $request);

            // Get pagination parameters
            $perPage = min($request->get('per_page', 20), 100); // Max 100 items per page
            
            // Paginate results
            $orders = $query->paginate($perPage);


            return OrderResource::collection($orders);

            // return response()->json([
            //     'success' => true,
            //     'data' => OrderResource::collection($orders)->response()->getData(),
            //     'meta' => [
            //         'total' => $orders->total(),
            //         'per_page' => $orders->perPage(),
            //         'current_page' => $orders->currentPage(),
            //         'last_page' => $orders->lastPage(),
            //         'from' => $orders->firstItem(),
            //         'to' => $orders->lastItem(),
            //     ],
            //     'filters_applied' => $request->only(['status', 'delivery_status', 'agent_id', 'rider_id', 'date_from', 'date_to', 'search'])
            // ]);

        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get validated data
            $validated = $request->validated();
            
            // Generate order number if not provided
            if (!isset($validated['order_no'])) {
                $validated['order_no'] = $this->generateOrderNumber();
            }

            // Create the order using the service
            $order = $this->orderService->createOrder($validated);

            // Log order creation
            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order)
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Eager load all necessary relationships
            $order = Order::with([
                'client',
                'orderItems.product',
                'vendor.products',
                'rider',
                'agent',
                // 'deliveryHistory',
                // 'paymentHistory'
            ])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new OrderResource($order)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => 'The requested order does not exist or has been deleted'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage
     */
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Find the order with relationships
            $order = Order::with(['client', 'orderItems', 'agent', 'rider'])
                ->whereNull('deleted_at')
                ->findOrFail($id);

            // Get validated data
            $validated = $request->validated();

            // Update the order using the service
            $updatedOrder = $this->orderService->updateOrder($order, $validated);

            // Log order update
            Log::info('Order updated successfully', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => auth()->id(),
                'changes' => $validated
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data' => new OrderResource($updatedOrder)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => 'The requested order does not exist or has been deleted'
            ], 404);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order: ' . $e->getMessage(), [
                'order_id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $order = Order::whereNull('deleted_at')->findOrFail($id);

            // Check if order can be deleted (business logic)
            if (!$this->canDeleteOrder($order)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be deleted',
                    'error' => 'Orders with status "processing" or "completed" cannot be deleted'
                ], 422);
            }

            // Soft delete the order
            $order->update(['deleted_at' => now()]);

            // Log order deletion
            Log::info('Order deleted successfully', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error' => 'The requested order does not exist or has been deleted'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting order: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Restore a soft deleted order
     */
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
            'created_at', 'updated_at', 'order_no', 'status', 
            'delivery_status', 'delivery_date', 'total_amount'
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


}
