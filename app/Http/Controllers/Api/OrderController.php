<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BulkOrderActionRequest;


use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\Order\OrderBulkActionService;
use Illuminate\Auth\Events\Validated;
// app/Services/Order/OrderBulkActionService.php
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    public function __construct(protected OrderBulkActionService $orderService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager load relationships and paginate
        $orders = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->whereNull('deleted_at')
            ->latest()
            ->paginate(20);

        // Return resource collection with pagination structure
        return OrderResource::collection($orders);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Eager load relationships for a single order
        $order = Order::with(['client', 'orderItems.product', 'vendor', 'rider', 'agent'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        // Return single order resource
        return new OrderResource($order);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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
}
