<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'order_no' => $this->order_no,
            'sku_no' => $this->sku_no,
            'tracking_no' => $this->tracking_no,
            'waybill_no' => $this->waybill_no,

            'client_id' => $this->client_id,
            'vendor_id' => $this->vendor_id,
            'driver_id' => $this->driver_id,
            'user_id' => $this->user_id,
            'rider_id' => $this->rider_id,
            'agent_id' => $this->agent_id,

            'total_price' => $this->total_price,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'shipping_charges' => $this->shipping_charges,
            'charges' => $this->charges,
            'invoice_value' => $this->invoice_value,
            'amount_paid' => $this->amount_paid,

            'status' => $this->status,
            'delivery_status' => $this->delivery_status,
            'payment_method' => $this->payment_method,
            'payment_id' => $this->payment_id,
            'mpesa_code' => $this->mpesa_code,
            'paypal' => $this->paypal,

            'customer_notes' => $this->customer_notes,
            'cancel_notes' => $this->cancel_notes,
            'return_notes' => $this->return_notes,
            'history_comment' => $this->history_comment,
            'terms' => $this->terms,
            'template_name' => $this->template_name,

            'platform' => $this->platform,
            'route' => $this->route,
            'weight' => $this->weight,
            'scale' => $this->scale,

            'delivery_date' => $this->delivery_date,
            'delivered_on' => $this->delivered_on,
            'cancelled_on' => $this->cancelled_on,
            'returned_on' => $this->returned_on,
            'return_date' => $this->return_date,
            'dispatched_on' => $this->dispatched_on,
            'schedule_date' => $this->schedule_date,
            'order_date' => $this->order_date,
            'printed_at' => $this->printed_at,
            'sticker_at' => $this->sticker_at,
            'archived_at' => $this->archived_at,
            'recall_date' => $this->recall_date,

            'pickup_address' => $this->pickup_address,
            'pickup_phone' => $this->pickup_phone,
            'pickup_shop' => $this->pickup_shop,
            'pickup_city' => $this->pickup_city,

            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'distance' => $this->distance,
            'geocoded' => (bool) $this->geocoded,

            'is_return_waiting_for_approval' => (bool) $this->is_return_waiting_for_approval,
            'is_salesreturn_allowed' => (bool) $this->is_salesreturn_allowed,
            'is_test_order' => (bool) $this->is_test_order,
            'is_emailed' => (bool) $this->is_emailed,
            'is_dropshipped' => (bool) $this->is_dropshipped,
            'is_cancel_item_waiting_for_approval' => (bool) $this->is_cancel_item_waiting_for_approval,
            'track_inventory' => (bool) $this->track_inventory,

            'confirmed' => (bool) $this->confirmed,
            'delivered' => (bool) $this->delivered,
            'returned' => (bool) $this->returned,
            'cancelled' => (bool) $this->cancelled,
            'invoiced' => (bool) $this->invoiced,
            'packed' => (bool) $this->packed,
            'printed' => (bool) $this->printed,
            'print_count' => $this->print_count,
            'sticker_printed' => (bool) $this->sticker_printed,
            'prepaid' => (bool) $this->prepaid,
            'paid' => (bool) $this->paid,

            'order_category_id' => $this->order_category_id,
            'ou_id' => $this->ou_id,
            'warehouse_id' => $this->warehouse_id,
            'country_id' => $this->country_id,
            'vehicle_id' => $this->vehicle_id,
            'zone_id' => $this->zone_id,

            'created_at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->toDateTimeString() : $this->created_at,
            'updated_at' => $this->updated_at instanceof \Carbon\Carbon ? $this->updated_at->toDateTimeString() : $this->updated_at,
            'client' => $this->whenLoaded('client'),
            'vendor' => $this->whenLoaded('vendor'),
            'order_items' => $this->whenLoaded('orderItems'),
        ];
    }
}
