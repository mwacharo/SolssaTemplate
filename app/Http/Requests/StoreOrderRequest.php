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

        // function called

    
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
            'order_no' => 'required|unique:orders,order_no',
            'reference' => 'nullable|string|max:255',
            // 'client_id' => 'required|exists:clients,id',
            // 'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'country_id' => 'required|exists:countries,id',
            // 'agent_id' => 'nullable|exists:users,id',
            // 'user_id' => 'required|exists:users,id',
            // 'rider_id' => 'nullable|exists:users,id',
            // 'zone_id' => 'nullable|exists:zones,id',
            'status' => 'required|string|max:50',
            'delivery_status' => 'nullable|string|max:50',
            // 'delivery_date' => 'nullable|date',
            // 'schedule_date' => 'nullable|date',
            'paid' => 'boolean',
            // 'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|exists:payments,id',
            'sub_total' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            // 'discount' => 'nullable|numeric|min:0',
            'shipping_charges' => 'nullable|numeric|min:0',
            // 'currency' => 'required|string|max:3',
            'weight' => 'nullable|numeric|min:0',
            'platform' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:50',
            // 'pickup_address' => 'nullable|string',
            // 'pickup_city' => 'nullable|string|max:100',
            // 'pickup_phone' => 'nullable|string|max:20',
            // 'pickup_shop' => 'nullable|string|max:100',
            // 'latitude' => 'nullable|numeric',
            // 'longitude' => 'nullable|numeric',
            'distance' => 'nullable|numeric|min:0',
            // 'customer_notes' => 'nullable|string',
            
            // For nested relationships
            'order_items' => 'nullable|array',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.sku' => 'nullable|string|max:100',
            'order_items.*.price' => 'required|numeric|min:0',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.discount' => 'nullable|numeric|min:0',
            'order_items.*.total' => 'required|numeric|min:0',
            'order_items.*.currency' => 'required|string|max:3',
            
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'required|in:shipping,pickup,billing,return,drop',
            'addresses.*.full_name' => 'required|string|max:255',
            'addresses.*.email' => 'nullable|email|max:255',
            'addresses.*.phone' => 'required|string|max:20',
            'addresses.*.address' => 'required|string',
            'addresses.*.city' => 'required|string|max:100',
            'addresses.*.region' => 'nullable|string|max:100',
            'addresses.*.country_id' => 'required|exists:countries,id',
            // 'addresses.*.zone_id' => 'nullable|exists:zones,id',
            'addresses.*.zipcode' => 'nullable|string|max:20',


            // customer and client details

            'customer.full_name' => 'nullable|string|max:255',
            'customer.email' => 'nullable|email|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.alt_phone' => 'nullable|string|max:20',
            'customer.address' => 'nullable|string',
            'customer.city' => 'nullable|string|max:100',
            'customer.region' => 'nullable|string|max:100',
            'customer.country_id' => 'nullable|exists:countries,id',
            'customer.zone_id' => 'nullable|exists:zones,id',
            'customer.zipcode' => 'nullable|string|max:20',
            'customer.is_spam' => 'boolean',

            // 'client.full_name' => 'required|string|max:255',
            // 'client.email' => 'nullable|email|max:255',
            // 'client.phone' => 'required|string|max:20',
            // 'client.alt_phone' => 'nullable|string|max:20',
            // 'client.address' => 'required|string',
            // 'client.city' => 'required|string|max:100',
            // 'client.region' => 'nullable|string|max:100',
            // 'client.country_id' => 'required|exists:countries,id',
            // 'client.zone_id' => 'nullable|exists:zones,id',
            // 'client.zipcode' => 'nullable|string|max:20',
            // 'client.is_spam' => 'boolean',
        ];
    }
}
