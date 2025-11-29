<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpeditionRequest;
use App\Http\Requests\UpdateExpeditionRequest;
use App\Models\Expedition;
use Illuminate\Support\Facades\DB;


class ExpeditionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //


        $expeditions = Expedition::with('shipmentItems.product', 'warehouse', 'vendor')->orderByDesc('id')->paginate(20);

        return response()->json([
            'success'     => true,
            'expeditions' => $expeditions
        ]);
    }

    public function store(StoreExpeditionRequest $request)
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            // Create expedition record
            $expedition = Expedition::create([
                'source_country'   => $data['source_country'],
                'warehouse_id'     => $data['warehouse_id'],
                'shipment_status'  => 'pending',
                'approval_status'   => 'pending',
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

            // Save shipment items
            foreach ($data['shipment_items'] as $item) {
                $expedition->shipmentItems()->create([
                    // 'product_name'   => $item['product']['name'],
                    // 'product_sku'    => $item['product']['sku'],
                    'quantity_sent'  => $item['quantity_sent'],
                    'expedition_id' => $expedition->id,
                    'product_id' => $item['product']['id'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => 'Expedition created successfully.',
                'expedition' => $expedition->load('shipmentItems', 'vendor', 'warehouse'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create expedition.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpeditionRequest $request, Expedition $expedition)
    {
        //


        $data = $request->validated();

        // Update expedition fields
        $expedition->update([
            'source_country'   => $data['source_country'] ?? $expedition->source_country,
            'destination'      => $data['destination'] ?? $expedition->destination,
            'shipment_date'    => $data['shipment_date'] ?? $expedition->shipment_date,
            'arrival_date'     => $data['arrival_date'] ?? $expedition->arrival_date,
            'transporter_name' => $data['transporter_name'] ?? $expedition->transporter_name,
            'tracking_number'  => $data['tracking_number'] ?? $expedition->tracking_number,
            'packages_number'  => $data['packages_number'] ?? $expedition->packages_number,
            'weight'           => $data['weight'] ?? $expedition->weight,
            'shipment_fees'    => $data['shipment_fees'] ?? $expedition->shipment_fees,
            'vendor_id'        => $data['vendor_id'] ?? $expedition->vendor_id,
        ]);

        // Handle shipment items if sent
        if (isset($data['shipment_items'])) {
            // Remove old items to avoid duplicates
            $expedition->items()->delete();

            // Insert new items
            foreach ($data['shipment_items'] as $item) {
                $expedition->items()->create([
                    'product_name'  => $item['product']['name'],
                    'product_sku'   => $item['product']['sku'],
                    'quantity_sent' => $item['quantity_sent'],
                ]);
            }
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Expedition updated successfully.',
            'expedition' => $expedition->load('items'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expedition $expedition)
    {
        // Delete child items first
        $expedition->shipmentItems()->delete();

        // Delete the expedition
        $expedition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expedition deleted successfully.'
        ]);
    }
}
