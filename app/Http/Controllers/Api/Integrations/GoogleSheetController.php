<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;


use App\Http\Requests\StoreGoogleSheetRequest;
use App\Http\Requests\UpdateGoogleSheetRequest;
use App\Http\Resources\GoogleSheetResource;
use App\Jobs\SyncGoogleSheetJob;
use App\Models\Customer;
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
            'data' => $sheets,
        ]);
    }





    public function store(StoreGoogleSheetRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $validatedData = $request->validated();

            if ($user) {
                $validatedData['user_id'] = $user->id;
                $validatedData['country_id'] = $user->country_id;
            }

            Log::info('Creating Google Sheet:', [
                'user_id' => $user->id ?? null,
                'user_email' => $user->email ?? 'guest',
                'country_id' => $user->country_id ?? null,
                'validated_data' => $validatedData
            ]);

            if (app()->isLocal() || config('app.debug')) {
                DB::enableQueryLog();
            }

            $googleSheet = GoogleSheet::create($validatedData);

            if (app()->isLocal() || config('app.debug')) {
                Log::info('Executed queries:', DB::getQueryLog());
            }

            $response = [
                'success' => true,
                'data' => new GoogleSheetResource($googleSheet)
            ];

            return response()->json($response, 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('DB Error on GoogleSheet create', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error creating Google Sheet',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error:', $e->errors());

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating Google Sheet:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Google Sheet',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }



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
            // $updated = $this->repository->update($sheet, $data);

            // use normal update method
            $sheet->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Google Sheet integration updated successfully',
                'data' => new GoogleSheetResource($sheet->fresh())
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
            $this->repository->destroy($sheet);

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


    // track empty
    // ingnoring the last order 
    // 

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

            // skip 
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



    // public function updateSheet(string $id): JsonResponse
    // {
    //     SyncGoogleSheetJob::dispatch($id);

    //     return response()->json([
    //         'status' => 'Sync job dispatched'
    //     ]);
    // }




    public function updateSheet(string $id, Request $request): JsonResponse
    {
        Log::info("updateSheet: Start syncing orders", ['sheet_id' => $id]);

        try {
            $sheet = $this->repository->findById($id);
            if (!$sheet) {
                Log::warning("updateSheet: Google Sheet integration not found", ['sheet_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Google Sheet integration not found'
                ], 404);
            }

            $vendorId = $sheet->vendor_id;
            $spreadsheetId = $sheet->post_spreadsheet_id;
            $sheetName = $sheet->sheet_name;

            Log::info("updateSheet: Integration loaded", [
                'vendor_id' => $vendorId,
                'spreadsheet_id' => $spreadsheetId,
                'sheet_name' => $sheetName
            ]);

            $orders = $this->fetchOrders($vendorId);
            Log::info("updateSheet: Orders fetched", ['count' => $orders->count()]);

            if ($orders->isEmpty()) {
                Log::info("updateSheet: No recent changes to orders", ['vendor_id' => $vendorId]);
                return response()->json(['message' => 'No recent changes'], 204);
            }


            $sheetMap = $this->fetchSheetOrders($spreadsheetId, $sheetName);
            Log::info("updateSheet: Sheet map fetched", ['sheet_map_count' => count($sheetMap)]);

            $changes = $this->getChangedOrders($orders, $sheetMap);
            Log::info("updateSheet: Changes computed", ['changes_count' => count($changes)]);

            if (empty($changes)) {
                Log::info("updateSheet: Sheet already up to date", ['sheet_id' => $id]);
                return response()->json(['message' => 'Sheet already up to date'], 200);
            }

            $result = $this->batchUpdateSheet($spreadsheetId, $sheetName, $changes);
            Log::info("updateSheet: Batch update completed", [
                'updated_rows' => count($changes),
                'google_response' => $result
            ]);

            return response()->json([
                'updated_rows' => count($changes),
                'status' => 'Synced successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("updateSheet: Exception during sync", [
                'sheet_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync sheet: ' . $e->getMessage()
            ], 500);
        }
    }


    protected function fetchOrders($vendorId)
    {
        // $since = now()->subMinutes(30);
        $since = now()->startOfDay();


        Log::info("fetchOrders: Fetching changed orders", [
            'vendor_id' => $vendorId,
            'since' => $since->toDateTimeString()
        ]);

        return Order::select('orders.*')
            ->with([
                'customer',
                'orderItems.product',
                'latestStatus.status',
                'assignments.user',
                'payments',
                'addresses',
                'callLogs'
            ])
            ->where('orders.vendor_id', $vendorId)
            ->where(function ($q) use ($since) {

                // 1. Order core fields
                $q->where('orders.updated_at', '>=', $since)

                    // 2. Status timeline changes
                    ->orWhereHas('statusTimestamps', function ($s) use ($since) {
                        $s->where('created_at', '>=', $since)
                            ->orWhere('updated_at', '>=', $since);
                    })

                    // 

                    // 3. Item changes
                    ->orWhereHas('orderItems', function ($i) use ($since) {
                        $i->where('updated_at', '>=', $since);
                    })

                    // 4. Assignment changes
                    ->orWhereHas('assignments', function ($a) use ($since) {
                        $a->where('updated_at', '>=', $since);
                    })

                    // 5. Address edits
                    ->orWhereHas('addresses', function ($ad) use ($since) {
                        $ad->where('updated_at', '>=', $since);
                    })

                    // 6. Payment updates
                    ->orWhereHas('payments', function ($p) use ($since) {
                        $p->where('updated_at', '>=', $since);
                    })

                    // 7. Customer profile change (important!)
                    ->orWhereHas('customer', function ($c) use ($since) {
                        $c->where('updated_at', '>=', $since);
                    })

                    // 8. Call log activity (optional but useful for ops tracking)
                    ->orWhereHas('callLogs', function ($cl) use ($since) {
                        $cl->where('updated_at', '>=', $since);
                    });
            })
            ->get();
    }


    public function fetchSheetOrders($spreadsheetId, $sheetName)
    {
        Log::info("fetchSheetOrders: Fetching orders from sheet", [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $sheetName
        ]);

        $googleSheetService = app(GoogleSheetService::class);


        // set spredsheet id
        $googleSheetService->setSpreadsheetId($spreadsheetId);




        // $response = $googleSheetService->readAllSheetData($sheetName);

        $rows = $googleSheetService->readAllSheetData($sheetName) ?? [];


        Log::info("fetchSheetOrders: Raw rows fetched", ['row_count' => count($rows)]);

        $map = [];

        foreach ($rows as $index => $row) {
            $orderNo = $row[1] ?? null; // Column B = Order Id
            if ($orderNo) {
                $map[$orderNo] = [
                    'row' => $index + 1,
                    'db_updated_at' => $row[15] ?? null // Col P
                ];
            } else {
                Log::debug("fetchSheetOrders: Skipping row with no order number", ['row_index' => $index + 2, 'row' => $row]);
            }
        }

        Log::info("fetchSheetOrders: Sheet map built", ['mapped_count' => count($map)]);

        return $map;
    }



    protected function mapOrderRow(Order $order)
    {
        $productNames = $order->orderItems->pluck('product.product_name')->implode(', ');
        $qty = $order->orderItems->sum('quantity');





        // helper to safely format date values (accepts DateTime, Carbon or string)
        $formatDate = function ($d) {
            if ($d instanceof \DateTimeInterface) {
                return $d->format('Y-m-d');
            }
            if (is_string($d) && !empty($d)) {
                try {
                    return \Carbon\Carbon::parse($d)->format('Y-m-d');
                } catch (\Exception $e) {
                    // If parsing fails, return the raw string (could already be in desired format)
                    return $d;
                }
            }
            return '';
        };

        // Use array_values to ensure it's a proper indexed array
        $row = array_values([
            $order->created_at->format('Y-m-d'),
            $order->order_no,
            $order->total_price,
            optional($order->customer)->full_name ?? '',
            optional($order->customer)->address ?? '',
            optional($order->customer)->phone ?? '',
            optional($order->customer)->alt_phone ?? '',
            optional($order->country)->name ?? '',
            optional($order->customer)->city?->name ?? '',

            // $productNames,mapOrderRow
            // $qty,

            // order has single order items 
            optional($order->orderItems)->isNotEmpty() ? $productNames : null,
            optional($order->orderItems)->isNotEmpty() ? $qty : null,

            // order mutiple order items
            // $order->orderItems->pluck('product.product_name')->implode(', '),
            // $order->orderItems->sum('quantity'),

            optional($order->latest_status->status)->name ?? '',
            // safe delivery date: prefer delivery_date, fall back to latest status updated_at
            $formatDate($order->delivery_date) ?: $formatDate($order->latest_status?->updated_at),

            trim(($order->customer_notes ?? '') . ' ' . ($order->latest_status->status_notes ?? '')),

            optional($order->assignments->first())->user->name ?? '',
            // $order->updated_at->toDateTimeString(), // Col P
            // now()->toDateTimeString()               // Col Q
        ]);
        Log::debug("mapOrderRow: Mapped order to row", [
            'order_no' => $order->order_no,
            'row_preview' => array_slice($row, 0, 6),
            'updated_at' => $order->updated_at->toDateTimeString()
        ]);

        return $row;
    }







    protected function batchUpdateSheet($spreadsheetId, $sheetName, $changes)
    {
        Log::info("batchUpdateSheet: Preparing batch update", [
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $sheetName,
            'changes_count' => count($changes)
        ]);

        $googleSheetService = app(GoogleSheetService::class)
            ->setSpreadsheetId($spreadsheetId);

        $service = $googleSheetService->sheetsService;
        $data = [];

        foreach ($changes as $item) {
            // $range = $sheetName . '!A' . $item['row'] . ':Q' . $item['row'];


            $rowValues = $this->mapOrderRow($item['order']);
            $columnCount = count($rowValues);

            $lastColumn = $this->getColumnLetter($columnCount);

            $range = "{$sheetName}!A{$item['row']}:{$lastColumn}{$item['row']}";


            $values = [$this->mapOrderRow($item['order'])];

            $data[] = new \Google\Service\Sheets\ValueRange([
                'range'  => $range,
                'values' => $values
            ]);
        }

        $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW',
            'data' => $data
        ]);

        return $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
    }


    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $temp = ($index - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $index = floor(($index - $temp - 1) / 26);
        }
        return $letter;
    }










    protected function getChangedOrders($orders, $sheetMap)
    {
        $toUpdate = [];
        $checked = 0;
        $changed = 0;

        foreach ($orders as $order) {

            $checked++;

            $sheet = $sheetMap[$order->order_no] ?? null;

            if (!$sheet) {
                continue;
            }

            $sheetUpdatedAt = $sheet['db_updated_at'] ?? null;

            $lastChange = $this->getOrderLastChangeTimestamp($order);

            $dbUpdatedAt = optional($lastChange)->toDateTimeString();

            if (!$sheetUpdatedAt || $sheetUpdatedAt !== $dbUpdatedAt) {

                $changed++;

                $toUpdate[] = [
                    'row'   => $sheet['row'],
                    'order' => $order
                ];

                Log::debug("Order changed → update required", [
                    'order_no' => $order->order_no,
                    'sheet_updated_at' => $sheetUpdatedAt,
                    'db_last_change' => $dbUpdatedAt
                ]);
            }
        }

        Log::info("Sheet Sync Summary", [
            'checked' => $checked,
            'changed' => $changed
        ]);

        return $toUpdate;
    }





    protected function prepareOrderData($orders)
    {
        Log::info("prepareOrderData: Preparing rows for append/update", ['count' => count($orders)]);
        $rows = [];

        foreach ($orders as $order) {

            $productNames = $order->orderItems->pluck('product.product_name')->implode(', ');
            $qty = $order->orderItems->sum('quantity');

            $rows[] = [
                $order->created_at->format('Y-m-d'),
                $order->order_no,
                $order->total_price,
                optional($order->customer)->full_name,
                optional($order->customer)->address,
                optional($order->customer)->phone,
                optional($order->customer)->alt_phone,
                optional($order->country)->name ?? 'Kenya',
                optional($order->customer)->city?->name,
                $productNames,
                $qty,
                optional($order->latest_status->status)->name,
                // status_notes

                optional($order->delivery_date)?->format('Y-m-d'),
                optional($order->latest_status)->status_notes,

                $order->customer_notes,
                optional($order->assignments->first())->user->name ?? ''
            ];
        }

        Log::info("prepareOrderData: Prepared rows", ['rows_count' => count($rows)]);
        return $rows;
    }



    protected function prepareResponse($result, $orders)
    {
        Log::info("prepareResponse: Preparing API response", [
            'orders_count' => count($orders),
            'result' => $result
        ]);

        return response()->json([
            'message' => 'Sheet updated successfully',
            'rows_added' => count($orders),
            'updatedRange' => $result->updates->updatedRange ?? null
        ]);
    }



    protected function getOrderLastChangeTimestamp(Order $order)
    {
        $timestamps = [
            $order->updated_at,
            optional($order->orderItems)->max('updated_at'),
            optional($order->statusTimestamps)->max('updated_at'),
            optional($order->assignments)->max('updated_at'),
            optional($order->payments)->max('updated_at'),
            optional($order->addresses)->max('updated_at'),
            optional($order->callLogs)->max('updated_at'),
            optional($order->customer)->updated_at,
        ];

        // Remove nulls
        $timestamps = array_filter($timestamps);

        return collect($timestamps)->max();
    }
}
