<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;


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
        $orderId = $this->route('id');

        return [
            'order_no' => [
                'sometimes',
                'string',
                // Rule::unique('orders')->ignore($orderId)
            ],
            'reference' => 'nullable|string|max:255',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'vendor_id' => 'sometimes|required|exists:users,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'country_id' => 'sometimes|required|exists:countries,id',
            'source' => 'nullable|string|max:50',
            'platform' => 'nullable|string|max:50',
            'currency' => 'sometimes|required|string|max:3',
            'sub_total' => 'sometimes|required|numeric|min:0',
            'total_price' => 'sometimes|required|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'paid' => 'boolean',
            'tracking_no' => 'nullable|string|max:100',
            'waybill_no' => 'nullable|string|max:100',
            'distance' => 'nullable|numeric|min:0',
            'geocoded' => 'nullable|boolean',
            'archived_at' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status_id' => 'sometimes|required|exists:statuses,id',
            'customer_notes' => 'nullable|string',
            'order_items' => 'nullable|array',
            'order_items.*.id' => 'sometimes|integer',
            'order_items.*.order_id' => 'sometimes|integer',
            'order_items.*.product_id' => 'nullable|exists:products,id',
            'order_items.*.sku' => 'nullable|string|max:100',
            'order_items.*.name' => 'nullable|string|max:255',
            'order_items.*.quantity' => 'required_with:order_items|integer|min:1',
            'order_items.*.unit_price' => 'nullable|numeric|min:0',
            'order_items.*.total_price' => 'nullable|numeric|min:0',
            'order_items.*.discount' => 'nullable|numeric|min:0',
            'order_items.*.currency' => 'nullable|string|max:3',
            'order_items.*.weight' => 'nullable|numeric|min:0',
            'order_items.*.delivered_quantity' => 'nullable|integer|min:0',
            'order_items.*.created_at' => 'nullable|date',
            'order_items.*.updated_at' => 'nullable|date',
            'order_items.*.deleted_at' => 'nullable|date',
            // 'order_items.*.product' => 'nullable|array', // if you want to validate nested product
            // 'order_items.*.editable' => 'nullable|boolean',
            'customer' => 'nullable|array',
            'customer.full_name' => 'nullable|string|max:255',
            'customer.phone' => 'nullable|string|max:20',
            'customer.city_id' => 'nullable|string|max:100',
            'customer.zone_id' => 'nullable|exists:zones,id',
            'customer.address' => 'nullable|string|max:255',
            'customer.region' => 'nullable|string|max:100',
            'customer.zipcode' => 'nullable|string|max:20',
            'customer.email' => 'nullable|email|max:255',
        ];
    }
}
