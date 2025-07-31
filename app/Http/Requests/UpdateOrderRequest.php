<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => 'sometimes|string|max:255',
            'drawer_id' => 'nullable|exists:drawers,id',
            'client_id' => 'nullable|exists:clients,id',
            'total_price' => 'nullable|numeric|min:0',
            'scale' => 'nullable|integer|min:1',
            'invoice_value' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'sub_total' => 'nullable|numeric|min:0',
            'order_no' => 'sometimes|string|max:255',
            'sku_no' => 'nullable|string|max:255',
            'tracking_no' => 'nullable|string|max:255',
            'waybill_no' => 'nullable|string|max:255',
            'customer_notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'charges' => 'nullable|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'status' => 'nullable|string|in:Inprogress,active,inactive',
            'delivery_status' => 'nullable|string|in:Inprogress,delivered,pending,cancelled',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'country_id' => 'nullable|exists:countries,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'paypal' => 'nullable|string',
            'payment_method' => 'nullable|string|max:255',
            'payment_id' => 'nullable|string|max:255',
            'mpesa_code' => 'nullable|string|max:255',
            'terms' => 'nullable|string|max:255',
            'template_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:255',
            'route' => 'nullable|string|max:255',
            'cancel_notes' => 'nullable|string',
            'is_return_waiting_for_approval' => 'nullable|boolean',
            'is_salesreturn_allowed' => 'nullable|boolean',
            'is_test_order' => 'nullable|boolean',
            'is_emailed' => 'nullable|boolean',
            'is_dropshipped' => 'nullable|boolean',
            'is_cancel_item_waiting_for_approval' => 'nullable|boolean',
            'track_inventory' => 'nullable|boolean',
            'confirmed' => 'nullable|boolean',
            'delivered' => 'nullable|boolean',
            'returned' => 'nullable|boolean',
            'cancelled' => 'nullable|boolean',
            'invoiced' => 'nullable|boolean',
            'packed' => 'nullable|boolean',
            'printed' => 'nullable|boolean',
            'print_count' => 'nullable|integer|min:0',
            'sticker_printed' => 'nullable|boolean',
            'prepaid' => 'nullable|boolean',
            'paid' => 'nullable|boolean',
            'weight' => 'nullable|numeric|min:0',
            'return_count' => 'nullable|integer|min:0',
            'dispatched_on' => 'nullable|date',
            'return_date' => 'nullable|date',
            'delivered_on' => 'nullable|date',
            'returned_on' => 'nullable|date',
            'cancelled_on' => 'nullable|date',
            'printed_at' => 'nullable|date',
            'print_no' => 'nullable|string|max:255',
            'sticker_at' => 'nullable|date',
            'recall_date' => 'nullable|date',
            'history_comment' => 'nullable|string',
            'return_notes' => 'nullable|string',
            // 'ou_id' => 'nullable|exists:organizational_units,id',
            'pickup_address' => 'nullable|string|max:255',
            'pickup_phone' => 'nullable|string|max:255',
            'pickup_shop' => 'nullable|string|max:255',
            'pickup_city' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'order_category_id' => 'nullable|exists:order_categories,id',
            'schedule_date' => 'nullable|date',
            'rider_id' => 'nullable|exists:riders,id',
            'agent_id' => 'nullable|exists:agents,id',
            'zone_id' => 'nullable|exists:zones,id',
            'checkout_id' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'distance' => 'nullable|numeric|min:0',
            'geocoded' => 'nullable|boolean',
            'loading_no' => 'nullable|string|max:255',
            'boxes' => 'nullable|string|max:255',
            'archived_at' => 'nullable|date',
            'order_date' => 'nullable|string|max:255',
            // Relationships
            'warehouse' => 'nullable|array',
            'warehouse.id' => 'nullable|exists:warehouses,id',
            'vendor' => 'nullable|array',
            'vendor.id' => 'nullable|exists:vendors,id',
            'country' => 'nullable|array',
            'country.id' => 'nullable|exists:countries,id',
            // Items
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
}
