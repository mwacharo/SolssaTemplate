<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => 'nullable|string|max:255',
            'drawer_id' => 'nullable|integer|exists:drawers,id',
            'client_id' => 'nullable|integer|exists:clients,id',
            'total_price' => 'nullable|numeric',
            'scale' => 'required|integer',
            'invoice_value' => 'nullable|numeric',
            'amount_paid' => 'nullable|numeric',
            'sub_total' => 'nullable|numeric',
            'order_no' => 'nullable|string|max:255',
            'sku_no' => 'nullable|string|max:255',
            'tracking_no' => 'nullable|string|max:255',
            'waybill_no' => 'nullable|string|max:255',
            'customer_notes' => 'nullable|string',
            'discount' => 'required|numeric',
            'shipping_charges' => 'required|numeric',
            'charges' => 'required|numeric',
            'delivery_date' => 'nullable|date',
            'status' => 'required|string|max:255',
            'delivery_status' => 'required|string|max:255',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'country_id' => 'nullable|integer|exists:countries,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'driver_id' => 'nullable|integer|exists:drivers,id',
            'paypal' => 'nullable|string',
            'payment_method' => 'nullable|string|max:255',
            'payment_id' => 'nullable|string|max:255',
            'mpesa_code' => 'nullable|string|max:255',
            'terms' => 'nullable|string|max:255',
            'template_name' => 'nullable|string|max:255',
            'platform' => 'required|string|max:255',
            'route' => 'nullable|string|max:255',
            'cancel_notes' => 'nullable|string',
            'is_return_waiting_for_approval' => 'required|boolean',
            'is_salesreturn_allowed' => 'required|boolean',
            'is_test_order' => 'required|boolean',
            'is_emailed' => 'required|boolean',
            'is_dropshipped' => 'required|boolean',
            'is_cancel_item_waiting_for_approval' => 'required|boolean',
            'track_inventory' => 'required|boolean',
            'confirmed' => 'required|boolean',
            'delivered' => 'required|boolean',
            'returned' => 'required|boolean',
            'cancelled' => 'required|boolean',
            'invoiced' => 'required|boolean',
            'packed' => 'required|boolean',
            'printed' => 'required|boolean',
            'print_count' => 'required|integer',
            'sticker_printed' => 'required|boolean',
            'prepaid' => 'required|boolean',
            'paid' => 'required|boolean',
            'weight' => 'required|numeric',
            'return_count' => 'required|integer',
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
            // 'ou_id' => 'nullable|integer|exists:organizational_units,id',
            'pickup_address' => 'nullable|string|max:255',
            'pickup_phone' => 'nullable|string|max:255',
            'pickup_shop' => 'nullable|string|max:255',
            'pickup_city' => 'nullable|string|max:255',
            'user_id' => 'required|integer|exists:users,id',
            'order_category_id' => 'nullable|integer|exists:order_categories,id',
            'schedule_date' => 'nullable|date',
            'rider_id' => 'nullable|integer|exists:riders,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'zone_id' => 'nullable|integer|exists:zones,id',
            'checkout_id' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'latitude' => 'nullable|string|max:255',
            'distance' => 'nullable|numeric',
            'geocoded' => 'required|boolean',
            'loading_no' => 'nullable|string|max:255',
            'boxes' => 'nullable|string|max:255',
            'archived_at' => 'nullable|date',
            'order_date' => 'nullable|string|max:255',
        ];
    }
}
