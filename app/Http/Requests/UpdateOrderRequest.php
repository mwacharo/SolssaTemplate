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
                'required',
                Rule::unique('orders')->ignore($orderId)
            ],
            'reference' => 'nullable|string|max:255',
            'client_id' => 'sometimes|required|exists:clients,id',
            'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'country_id' => 'sometimes|required|exists:countries,id',
            'agent_id' => 'nullable|exists:users,id',
            'user_id' => 'sometimes|required|exists:users,id',
            'rider_id' => 'nullable|exists:users,id',
            'zone_id' => 'nullable|exists:zones,id',
            'status' => 'sometimes|required|string|max:50',
            'delivery_status' => 'nullable|string|max:50',
            'delivery_date' => 'nullable|date',
            'schedule_date' => 'nullable|date',
            'paid' => 'boolean',
            'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|exists:payments,id',
            'sub_total' => 'sometimes|required|numeric|min:0',
            'total_price' => 'sometimes|required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|required|string|max:3',
            'weight' => 'nullable|numeric|min:0',
            'platform' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:50',
            'pickup_address' => 'nullable|string',
            'pickup_city' => 'nullable|string|max:100',
            'pickup_phone' => 'nullable|string|max:20',
            'pickup_shop' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance' => 'nullable|numeric|min:0',
            'customer_notes' => 'nullable|string',
            
            // For nested relationships
            'order_items' => 'nullable|array',
            'order_items.*.product_id' => 'required_with:order_items|exists:products,id',
            'order_items.*.sku' => 'nullable|string|max:100',
            'order_items.*.price' => 'required_with:order_items|numeric|min:0',
            'order_items.*.quantity' => 'required_with:order_items|integer|min:1',
            'order_items.*.discount' => 'nullable|numeric|min:0',
            'order_items.*.total' => 'required_with:order_items|numeric|min:0',
            'order_items.*.currency' => 'required_with:order_items|string|max:3',
        ];
    }
}
