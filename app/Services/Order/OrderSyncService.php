<?php

namespace App\Services\Order;

use App\Models\GoogleSheet;
use App\Repositories\Interfaces\GoogleSheetRepositoryInterface;
use App\Services\Order\Sources\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    protected $googleSheetService;
    protected $googleSheetRepository;

    public function __construct(
        GoogleSheetService $googleSheetService,
        GoogleSheetRepositoryInterface $googleSheetRepository
    ) {
        $this->googleSheetService = $googleSheetService;
        $this->googleSheetRepository = $googleSheetRepository;
    }

    /**
     * Sync orders to Google Sheets
     *
     * @param GoogleSheet $sheet The sheet configuration
     * @param array $orders The orders to sync
     * @return bool
     */
    public function syncOrders(GoogleSheet $sheet, array $orders)
    {
        try {
            Log::info('Starting syncOrders', [
                'spreadsheet_id' => $sheet->post_spreadsheet_id,
                'orders_count' => count($orders)
            ]);

            $this->googleSheetService->setSpreadsheetId($sheet->post_spreadsheet_id);

            // Skip header row if orders already contain it
            $values = [];
            $hasHeader = false;
            
            // Check if first row is a header
            if (isset($orders[0]) && is_array($orders[0]) && 
                (isset($orders[0][0]) && $orders[0][0] === 'Order Number' || 
                 isset($orders[0]['Order Number']))) {
                $hasHeader = true;
            }
            
            if (!$hasHeader) {
                // Add headers if not present
                $headers = ['Order Number', 'Date', 'Customer', 'Total', 'Status'];
                $values[] = $headers;
            }

            // Add data rows
            foreach ($orders as $order) {
                if ($hasHeader && $order === $orders[0]) {
                    // Skip adding the header row again
                    continue;
                }
                
                // Handle both array and object formats
                if (is_array($order)) {
                    $values[] = $order;
                } else {
                    $values[] = [
                        $order->order_number ?? '',
                        $order->created_at ? (is_string($order->created_at) ? $order->created_at : $order->created_at->format('Y-m-d H:i:s')) : '',
                        $order->customer_name ?? '',
                        $order->total ?? '',
                        $order->status ?? ''
                    ];
                }
            }

            $range = 'Orders!A1:E' . (count($values) + 1);
            
            Log::debug('Prepared values for update', [
                'range' => $range,
                'values_count' => count($values),
                'values_preview' => array_slice($values, 0, 3) // show only first 3 rows for brevity
            ]);

            // Update the sheet
            $result = $this->googleSheetService->updateSheetData($range, $values);

            if ($result) {
                // Update the record in the repository
                $this->googleSheetRepository->updateLastOrderSync($sheet, end($values)[0] ?? null);
                Log::info('Orders synced successfully');
            } else {
                Log::warning('Failed to update sheet data for orders');
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Order sync error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Sync products to Google Sheets
     *
     * @param GoogleSheet $sheet The sheet configuration
     * @param array $products The products to sync
     * @return bool
     */
    public function syncProducts(GoogleSheet $sheet, array $products)
    {
        try {
            $this->googleSheetService->setSpreadsheetId($sheet->post_spreadsheet_id);
            
            $values = [];
            $headers = ['Product ID', 'Name', 'SKU', 'Price', 'Stock'];
            $values[] = $headers;
            
            foreach ($products as $product) {
                if (is_array($product)) {
                    $values[] = [
                        $product['id'] ?? '',
                        $product['name'] ?? '',
                        $product['sku'] ?? '',
                        $product['price'] ?? '',
                        $product['stock'] ?? ''
                    ];
                } else {
                    $values[] = [
                        $product->id ?? '',
                        $product->name ?? '',
                        $product->sku ?? '',
                        $product->price ?? '',
                        $product->stock ?? ''
                    ];
                }
            }
            
            $range = 'Products!A1:E' . (count($products) + 1);
            
            // Update the sheet
            $result = $this->googleSheetService->updateSheetData($range, $values);
            
            if ($result) {
                // Update the last synced information
                $this->googleSheetRepository->updateLastProductSync($sheet);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Product sync error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process data from the sheet
     *
     * @param array $values
     * @param array $headers
     * @param GoogleSheet $sheet
     * @return array
     */
    public function processSheetData($values, $headers, $sheet)
    {
        $headers = array_map('strtolower', array_map('trim', $headers));
        $values = array_map('array_map', array_fill(0, count($values), 'trim'), $values);
        $orderData = [];

        foreach ($values as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $row = array_pad($row, count($headers), null);
            $data = array_combine($headers, $row);

            $orderNo = $data['order id'] ?? null;
            $invoiceNo = $data['invoice number'] ?? null;

            if (empty($orderNo) && empty($invoiceNo)) {
                continue;
            }

            if (!isset($orderData[$orderNo])) {
                $orderData[$orderNo] = $this->createOrderData($data, $sheet);
            }

            $orderData[$orderNo]['products'][] = $this->createProductData($data);
        }

        return $orderData;
    }

    /**
     * Create order data from sheet row
     * 
     * @param array $data
     * @param GoogleSheet $sheet
     * @return array
     */
    private function createOrderData($data, $sheet)
    {
        $cod_amount = str_replace(',', '', $data['cod amount'] ?? null);
        return [
            'order_no' => $data['order id'] ?? null,
            'invoice_no' => $data['invoice number'] ?? null,
            'cod_amount' => is_numeric($cod_amount) ? $cod_amount : null,
            'client_name' => $data['client name'] ?? null,
            'address' => $data['address'] ?? null,
            'country' => $data['receier country*'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'products' => [],
            'quantity' => $data['quantity'] ?? null,
            'status' => $data['status'] ?? null,
            'delivery_date' => $data['delivery date'] ?? null,
            'special_instruction' => $data['special instructiuons'] ?? null,
            'distance' => 0,
            'invoice_value' => 0,
            'pod_returned' => isset($data['documents received ']) && strtolower($data['documents received ']) === 'yes' ? 1 : 0,
            'vendor_id' => $sheet->vendor_id,
            'branch_id' => $sheet->branch_id,
        ];
    }

    /**
     * Create product data from sheet row
     * 
     * @param array $data
     * @return array
     */
    private function createProductData($data)
    {
        return [
            'sku_number' => $data['sku number'] ?? null,
            'product_name' => $data['product name'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'boxes' => $data['boxes'] ?? null,
            'weight' => $data['weight'] ?? null,
        ];
    }

    /**
     * Prepare order data for Google Sheet update
     * 
     * @param array $orders
     * @return array
     */
    public function prepareOrderData($orders)
    {
        $updateData = [];

        foreach ($orders as $order) {
            $updateData[$order->order_no] = [
                'status' => $order->status ?? '',
                'pod' => $order->pod ?? '',
                'special_instruction' => $order->special_instruction ?? '',
            ];
        }

        Log::debug("Prepared update data for " . count($updateData) . " orders");
        return $updateData;
    }
}