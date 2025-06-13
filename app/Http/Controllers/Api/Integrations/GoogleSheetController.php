<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;


use App\Http\Requests\StoreGoogleSheetRequest;
use App\Http\Requests\UpdateGoogleSheetRequest;
use App\Models\Client;
use App\Models\GoogleSheet;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Repositories\GoogleSheetRepository;
use App\Repositories\Interfaces\GoogleSheetRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Services\Order\OrderService;
use App\Services\Order\OrderSyncService as OrderOrderSyncService;
use App\Services\OrderSyncService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Order\Sources\GoogleSheetService;
use Illuminate\Support\Facades\DB;

class GoogleSheetController extends Controller
{

    protected $repository;
    protected $orderRepository;
    protected $googleSheetRepository;
    protected $googleSheetService;
    protected $orderService;
    protected $productService;
    protected $orderSyncService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(
        GoogleSheetRepository $repository,
        OrderRepository $orderRepository,
        OrderService $orderService,
        ProductService $productService,
        GoogleSheetService $googleSheetService
    ) {
        $this->repository = $repository;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->googleSheetService = $googleSheetService;
        $this->orderSyncService = new OrderOrderSyncService($googleSheetService, $repository);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->query('vendor_id');
        $sheets = $vendorId
            ? GoogleSheet::with('vendor')->where('vendor_id', $vendorId)->get()
            : GoogleSheet::with('vendor')->get();

        return response()->json([
            'success' => true,
            'data' => $sheets
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGoogleSheetRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $sheet = $this->repository->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Google Sheet integration created successfully',
                'data' => $sheet
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create Google Sheet integration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Sheet integration'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $sheet = $this->repository->findById($id);

        if (!$sheet) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet integration not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sheet
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGoogleSheetRequest $request, string $id): JsonResponse
    {
        $sheet = $this->repository->findById($id);

        if (!$sheet) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet integration not found'
            ], 404);
        }

        try {
            $data = $request->validated();
            $updated = $this->repository->update($sheet, $data);

            return response()->json([
                'success' => true,
                'message' => 'Google Sheet integration updated successfully',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Google Sheet integration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update Google Sheet integration'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $sheet = $this->repository->findById($id);

        if (!$sheet) {
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet integration not found'
            ], 404);
        }

        try {
            $this->repository->delete($sheet);

            return response()->json([
                'success' => true,
                'message' => 'Google Sheet integration deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete Google Sheet integration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Google Sheet integration'
            ], 500);
        }
    }

    //  the puporse of this function is to upload orders from a Google Sheet to the database
    /**
     * Upload orders from Google Sheets to the database
     */
    public function uploadOrders(string $id, Request $request): JsonResponse
    {
        Log::info("syncOrders: Start syncing orders", ['sheet_id' => $id]);

        $sheet = $this->repository->findById($id);

        if (!$sheet) {
            Log::warning("syncOrders: Google Sheet integration not found for ID {$id}");
            return response()->json([
                'success' => false,
                'message' => 'Google Sheet integration not found'
            ], 404);
        }

        try {
            $filters = $request->all();
            Log::info("syncOrders: Fetching orders for sync", ['sheet_id' => $id, 'filters' => $filters]);

            // Set the spreadsheetId before calling any Google Sheet methods
            $this->googleSheetService->setSpreadsheetId($sheet->post_spreadsheet_id);

            // Read all data from the sheet
            $sheetData = $this->googleSheetService->readAllSheetData($sheet->sheet_name);

            Log::info("syncOrders: Orders fetched", ['count' => count($sheetData)]);

            if (empty($sheetData) || count($sheetData) < 2) {
                Log::info("syncOrders: No new orders to sync for sheet ID {$id}");
                return response()->json([
                    'success' => true,
                    'message' => 'No new orders to sync'
                ]);
            }

            // The first row is headers
            $headers = array_map('strtolower', array_map('trim', $sheetData[0]));
            $values = array_slice($sheetData, 1);

            // Process the sheet data
            // $orderData = $this->googleSheetService->processSheetData($values, $headers, $sheet);

            $orderData = $this->orderSyncService->processSheetData($values, $headers, $sheet);

            Log::info("syncOrders: Processed order data", [
                'sheet_id' => $id,
                'order_count' => count($orderData),
                'order_nos' => $orderData
            ]);

            if (empty($orderData)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No valid orders found in sheet'
                ]);
            }

            // Check for duplicate orders (by order_no)
            $orderNos = array_filter(array_keys($orderData));
            $existingOrders = Order::whereIn('order_no', $orderNos)->pluck('order_no')->toArray();

            $duplicates = [];
            $newOrders = [];

            foreach ($orderData as $orderNo => $data) {
                if (in_array($orderNo, $existingOrders)) {
                    $duplicates[] = $orderNo;
                } else {
                    $newOrders[$orderNo] = $data;
                }
            }

            // Save new orders
            $syncedCount = 0;
            if (!empty($newOrders)) {
                $syncedCount = $this->orderRepository->saveOrderData($newOrders, $request->user()->id ?? null, $sheet);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order sync completed',
                'synced_count' => $syncedCount,
                'duplicates' => $duplicates,
                'new_orders' => array_keys($newOrders)
            ]);
        } catch (\Exception $e) {
            Log::error('Order sync failed: ' . $e->getMessage(), [
                'sheet_id' => $id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order sync failed: ' . $e->getMessage()
            ], 500);
        }
    }



    public function updateSheet(Request $request)
    {
        try {
            $validatedData = $this->validateRequest($request);
            $vendorId = $validatedData['item']['vendor_id'];
            $sheetName = $validatedData['item']['sheet_name'];
            $spreadsheetId = $validatedData['item']['post_spreadsheet_id'];

            $orders = $this->fetchOrders($vendorId);

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found for this vendor'], 204);
            }

            $updateData = $this->prepareOrderData($orders);
            $result = $this->updateGoogleSheet($spreadsheetId, $sheetName, $updateData);

            return $this->prepareResponse($result, $orders);
        } catch (\Exception $e) {
            Log::error('Error updating sheet: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while updating the sheet'], 500);
        }
    }
}
