<?php

namespace App\Repositories;

use App\Models\GoogleSheet;
use App\Models\Order;
use App\Repositories\Interfaces\GoogleSheetRepositoryInterface;
use App\Services\Order\Sources\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleSheetRepository implements GoogleSheetRepositoryInterface
{
    /**
     * Find a Google Sheet by ID
     *
     * @param string $id
     * @return GoogleSheet|null
     */
    public function findById($id)
    {
        return GoogleSheet::find($id);
    }


    // add create method
    /**
     * Create a new Google Sheet record
     *
     * @param array $data
     * @return GoogleSheet
     */
    public function create(array $data)
    {
        // return GoogleSheet::create($data);

        try {
            Log::info('Creating Google Sheet with data:', $data);

            $sheet = GoogleSheet::create($data);

            Log::info('Google Sheet created successfully:', ['id' => $sheet->id]);

            return $sheet;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Sheet in repository: ' . $e->getMessage());
            Log::error('Data that failed:', $data);
            throw $e;
        }
    }

    /**
     * Update the last order sync information
     *
     * @param GoogleSheet $sheet
     * @param string|null $lastOrderNumber
     * @return bool
     */
    public function updateLastOrderSync(GoogleSheet $sheet, $lastOrderNumber = null)
    {
        $sheet->last_order_synced = Carbon::now();

        if ($lastOrderNumber) {
            $sheet->last_updated_order_number = $lastOrderNumber;
        }

        return $sheet->save();
    }

    /**
     * Update the last product sync information
     *
     * @param GoogleSheet $sheet
     * @return bool
     */
    public function updateLastProductSync(GoogleSheet $sheet)
    {
        $sheet->last_product_synced = Carbon::now();
        return $sheet->save();
    }

    /**
     * Track product sync
     *
     * @param GoogleSheet $sheet
     * @return bool
     */
    public function trackProductSync(GoogleSheet $sheet)
    {
        $sheet->last_product_synced = Carbon::now();
        return $sheet->save();
    }

    public function update(GoogleSheet $sheet)
    {
        return $sheet->save();
    }


    /**
     * Delete a Google Sheet record
     *
     * @param GoogleSheet $sheet
     * @return bool|null
     * @throws \Exception
     */
    public function destroy(GoogleSheet $sheet)
    {
        return $sheet->delete();
    }




    public function fetchOrders($vendorId)
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



    public function mapOrderRow(Order $order)
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







    public function batchUpdateSheet($spreadsheetId, $sheetName, $changes)
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










    public function getChangedOrders($orders, $sheetMap)
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





    public function prepareOrderData($orders)
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



    public function prepareResponse($result, $orders)
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



    public function getOrderLastChangeTimestamp(Order $order)
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
