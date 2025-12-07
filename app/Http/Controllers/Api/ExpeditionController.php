<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpeditionRequest;
use App\Http\Requests\UpdateExpeditionRequest;
use App\Models\Expedition;
use Illuminate\Support\Facades\DB;
use Opcodes\LogViewer\Logs\Log;

class ExpeditionController extends Controller
{
    public function index()
    {
        return response()->json([
            'success'     => true,
            'expeditions' => Expedition::with([
                'shipmentItems.product',
                'warehouse',
                'vendor'
            ])->latest()->paginate(20)
        ]);
    }

    public function store(StoreExpeditionRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {

            $expedition = Expedition::create([
                'source_country'   => $data['source_country'],
                'warehouse_id'     => $data['warehouse_id'],
                'shipment_status'  => 'pending',
                'approval_status'  => 'pending',
                'transporter_reimbursement_status' => 'pending',
                'shipment_date'    => $data['shipment_date'],
                'arrival_date'     => $data['arrival_date'],
                'transporter_name' => $data['transporter_name'],
                'tracking_number'  => $data['tracking_number'],
                'packages_number'  => $data['packages_number'],
                'weight'           => $data['weight'],
                'shipment_fees'    => $data['shipment_fees'],
                'vendor_id'        => $data['vendor_id'] ?? null,
            ]);

            foreach ($data['shipment_items'] as $item) {
                $expedition->shipmentItems()->create([
                    'product_id'    => $item['product']['id'],
                    'quantity_sent' => $item['quantity_sent'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Expedition created successfully.',
                'expedition' => $expedition->load([
                    'shipmentItems.product',
                    'vendor',
                    'warehouse'
                ]),
            ], 201);
        });
    }

    // public function update(UpdateExpeditionRequest $request, Expedition $expedition)
    // {
    //     $data = $request->validated();

    //     return DB::transaction(function () use ($data, $expedition) {

    //         /**
    //          * 1. UPDATE EXPEDITION FIELDS
    //          */
    //         $expedition->update([
    //             'source_country'   => $data['source_country']   ?? $expedition->source_country,
    //             'destination'      => $data['destination']      ?? $expedition->destination,
    //             'warehouse_id'     => $data['warehouse_id']     ?? $expedition->warehouse_id,
    //             'shipment_date'    => $data['shipment_date']    ?? $expedition->shipment_date,
    //             'arrival_date'     => $data['arrival_date']     ?? $expedition->arrival_date,
    //             'transporter_name' => $data['transporter_name'] ?? $expedition->transporter_name,
    //             'tracking_number'  => $data['tracking_number']  ?? $expedition->tracking_number,
    //             'packages_number'  => $data['packages_number']  ?? $expedition->packages_number,
    //             'weight'           => $data['weight']           ?? $expedition->weight,
    //             'shipment_fees'    => $data['shipment_fees']    ?? $expedition->shipment_fees,
    //             'vendor_id'        => $data['vendor_id']        ?? $expedition->vendor_id,
    //         ]);

    //         // log the expedition update
    //         \Log::info('Expedition updated', ['expedition_id' => $expedition->id]);


    //         /**
    //          * 2. HANDLE SHIPMENT ITEMS
    //          */
    //         $submittedIds = [];

    //         if (!empty($data['shipment_items'])) {

    //             foreach ($data['shipment_items'] as $item) {

    //                 // CASE A: Update existing item
    //                 if (!empty($item['id'])) {

    //                     $shipmentItem = $expedition->shipmentItems()
    //                         ->where('id', $item['id'])
    //                         ->first();

    //                     if ($shipmentItem) {
    //                         $shipmentItem->update([
    //                             'product_id'    => $item['product']['id'],
    //                             'quantity_sent' => $item['quantity_sent'],
    //                             'expedition_id'  => $expedition->id, // ALWAYS SET
    //                         ]);

    //                         $submittedIds[] = $shipmentItem->id;
    //                         continue;
    //                     }
    //                 }

    //                 // CASE B: Create new item
    //                 $newItem = $expedition->shipmentItems()->create([
    //                     'expedition_id'  => $expedition->id, // ALWAYS SET
    //                     'product_id'     => $item['product']['id'],
    //                     'quantity_sent'  => $item['quantity_sent'],
    //                 ]);

    //                 $submittedIds[] = $newItem->id;
    //             }

    //             // CASE C: Delete items removed from submission
    //             $expedition->shipmentItems()
    //                 ->whereNotIn('id', $submittedIds)
    //                 ->delete();
    //         }

    //         return response()->json([
    //             'success'    => true,
    //             'message'    => 'Expedition updated successfully.',
    //             'expedition' => $expedition->fresh()->load([
    //                 'shipmentItems.product',
    //                 'vendor',
    //                 'warehouse'
    //             ]),
    //         ]);
    //     });
    // }


    public function update(UpdateExpeditionRequest $request, $id)
    {
        $expedition = Expedition::findOrFail($id);
        $data = $request->validated();

        return DB::transaction(function () use ($data, $expedition) {

            /*
        |--------------------------------------------------------------------------
        | 1. UPDATE EXPEDITION FIELDS (ID SAFE)
        |--------------------------------------------------------------------------
        */
            $expedition->update(collect($data)->except(['id'])->toArray());


            /*
        |--------------------------------------------------------------------------
        | 2. HANDLE SHIPMENT ITEMS
        |--------------------------------------------------------------------------
        */
            $submittedIds = [];

            foreach ($data['shipment_items'] ?? [] as $item) {

                // Allow product_id OR product.id
                $productId = $item['product_id']
                    ?? ($item['product']['id'] ?? null);

                if (!$productId) {
                    throw new \Exception("Missing product ID for shipment item.");
                }


                /*----------------------------------------------------------
             | A. UPDATE EXISTING ITEM
             ----------------------------------------------------------*/
                if (!empty($item['id'])) {

                    $existing = $expedition->shipmentItems()
                        ->where('id', $item['id'])
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'product_id'    => $productId,
                            'quantity_sent' => $item['quantity_sent'],
                            'expedition_id' => $expedition->id,
                        ]);

                        $submittedIds[] = $existing->id;
                        continue;
                    }
                }


                /*----------------------------------------------------------
             | B. CREATE NEW ITEM
             ----------------------------------------------------------*/
                $new = $expedition->shipmentItems()->create([
                    'expedition_id' => $expedition->id,
                    'product_id'    => $productId,
                    'quantity_sent' => $item['quantity_sent'],
                ]);

                $submittedIds[] = $new->id;
            }


            /*--------------------------------------------------------------
         | C. DELETE ITEMS REMOVED FROM REQUEST
         --------------------------------------------------------------*/
            $expedition->shipmentItems()
                ->whereNotIn('id', $submittedIds)
                ->delete();


            /*
        |--------------------------------------------------------------------------
        | 3. RETURN UPDATED EXPEDITION
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'success'    => true,
                'message'    => 'Expedition updated successfully.',
                'expedition' => $expedition->fresh()->load([
                    'shipmentItems.product',
                    'vendor',
                    'warehouse',
                ]),
            ]);
        });
    }



    public function destroy($id)
    {
        $expedition = Expedition::findOrFail($id);

        $expedition->shipmentItems()->delete();
        $expedition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expedition deleted successfully.'
        ]);
    }
}
