<?php

namespace App\Repositories;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get orders by vendor ID and status
     *
     * @param int $vendorId
     * @param array $statuses
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersByVendorAndStatus($vendorId, $statuses = ['pending', 'picked', 'delivered'])
    {
        return Order::where('vendor_id', $vendorId)
            ->whereIn('status', $statuses)
            ->get();
    }

    /**
     * Save order data from Google Sheets
     *
     * @param array $orderData
     * @param int $userId
     * @param GoogleSheet $sheet
     * @return int
     */
    public function saveOrderData($orderData, $userId, $sheet)
    {
        $syncedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($orderData as $data) {
                $client = $this->createOrUpdateClient($data, $userId, $sheet);
                $order = $this->createOrder($data, $client, $userId, $sheet);
                $this->createOrderProducts($data['products'], $order, $sheet);

                $syncedCount++;
                // Optional: Dispatch geocoding job or other post-processing
                // GeocodeAddress::dispatch($order);
            }

            DB::commit();
            return $syncedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving order data: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Create or update client from order data
     *
     * @param array $data
     * @param int $userId
     * @param GoogleSheet $sheet
     * @return Client
     */
    private function createOrUpdateClient($data, $userId, $sheet)
    {
        try {
            Log::info('Attempting to create or update client', [
                'phone_number' => $data['phone'],
                'user_id' => $userId,
                'branch_id' => $sheet->branch_id,
                'vendor_id' => $sheet->vendor_id,
                'name' => $data['client_name'],
            ]);

            $client = Client::updateOrCreate(
                ['phone_number' => $data['phone']],
                [
                    'user_id' => $userId,
                    'branch_id' => $sheet->branch_id,
                    'vendor_id' => $sheet->vendor_id,
                    'name' => $data['client_name'],
                    'email' => $data['email'] ?? null,
                    'alt_phone' => $data['alt phone'] ?? null,
                    'address' => $data['address'],
                    'city' => $data['city'] ?? null,
                ]
            );

            Log::info('Client created or updated successfully', [
                'client_id' => $client->id,
                'phone_number' => $client->phone_number,
            ]);

            return $client;
        } catch (\Exception $e) {
            Log::error('Error creating or updating client', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Create order if it doesn't exist
     *
     * @param array $data
     * @param Client $client
     * @param int $userId
     * @param GoogleSheet $sheet
     * @return Order
     */
    private function createOrder($data, $client, $userId, $sheet)
    {
        $existingOrder = Order::where('order_no', $data['order_no'])->first();

        if ($existingOrder) {
            return $existingOrder;
        }

        return Order::create([
            'order_no' => $data['order_no'],
            'client_id' => $client->id,
            'client_name' => $data['client_name'],
            'cod_amount' => $data['cod_amount'],
            'total_price' => $data['cod_amount'],
            'address' => $data['address'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'status' => $data['status'],
            'delivery_date' => $data['delivery_date'],
            'special_instruction' => $data['special_instruction'],
            'distance' => $data['distance'],
            'invoice_value' => $data['invoice_value'],
            'pod_returned' => $data['pod_returned'],
            'user_id' => $userId,
            'branch_id' => $sheet->branch_id,
            'vendor_id' => $sheet->vendor_id,
            'country_id' => $sheet->country_id,
        ]);
    }

    /**
     * Create order products
     * 
     * @param array $products
     * @param Order $order
     * @param GoogleSheet $sheet
     * @return void
     */
    private function createOrderProducts($products, $order, $sheet)
    {
        $orderProducts = [];

        foreach ($products as $productData) {
            try {
                $product = Product::updateOrCreate(
                    [
                        'product_name' => $productData['product_name'],
                        'sku_no' => $productData['sku_number'],
                        'vendor_id' => $sheet->vendor_id,
                        'country_id' => $sheet->country_id,
                    ]
                );

                // $weight = !empty($productData['weight']) && is_numeric($productData['weight'])
                //     ? intval($productData['weight'])
                //     : 0;

                $quantity = !empty($productData['quantity']) && is_numeric($productData['quantity'])
                    ? intval($productData['quantity'])
                    : 1;

                $orderProducts[] = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => 0,
                    // 'weight' => $weight,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'vendor_id' => $sheet->vendor_id,
                ];

                Log::info('Prepared OrderProduct', [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    // 'weight' => $weight,
                    'vendor_id' => $sheet->vendor_id,

                ]);
            } catch (\Exception $e) {
                Log::error('Error preparing OrderProduct', [
                    'error' => $e->getMessage(),
                    'product_data' => $productData,
                ]);
            }
        }

        if (!empty($orderProducts)) {
            OrderItem::insert($orderProducts);
            Log::info('Inserted OrderProducts', ['count' => count($orderProducts)]);
        }
    }
}
