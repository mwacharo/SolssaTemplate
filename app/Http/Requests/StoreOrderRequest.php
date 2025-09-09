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
          'warehouse_id' => 'required|exists:warehouses,id',
          'country_id' => 'required|exists:countries,id',
        //   'vendor_id' => [
        //      function ($attribute, $value, $fail) {
        //         $user = $this->user();

        //         // If user is a vendor, they don't need to provide vendor_id
        //         if ($user && $user->hasRole('Vendor')) {
        //             return;
        //         }

        //         // If not a vendor, vendor_id is required and must exist
        //         if (empty($value)) {
        //             $fail('Vendor ID is required.');
        //             return;
        //         }

        //         if (!\App\Models\Vendor::where('id', $value)->exists()) {
        //             $fail('The selected vendor id is invalid.');
        //         }
        //      }
        //   ],
        'vendor_id' => 'nullable|exists:users,id',
          // 'agent_id' => 'nullable|exists:users,id',
          // 'user_id' => 'required|exists:users,id',
          // 'rider_id' => 'nullable|exists:users,id',
          // 'zone_id' => 'nullable|exists:zones,id',
          'status_id' => 'required|integer',
          'delivery_status_id' => 'nullable|integer',
          // 'delivery_date' => 'nullable|date',
          // 'schedule_date' => 'nullable|date',
          'paid' => 'boolean',
          // 'payment_method' => 'nullable|string|max:50',
          'payment_id' => 'nullable|exists:payments,id',
          // 'sub_total' => 'required|numeric|min:0',
          // 'total_price' => 'required|numeric|min:0',
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
          'order_items.*.unit_price' => 'required|numeric|min:0',
          'order_items.*.quantity' => 'required|integer|min:1',
          // 'order_items.*.discount' => 'nullable|numeric|min:0',
          // 'order_items.*.total' => 'required|numeric|min:0',
          // 'order_items.*.currency' => 'required|string|max:3',

          'addresses' => 'nullable|array',
          'addresses.*.type' => 'required|in:shipping,pickup,billing,return,drop',
          'addresses.*.full_name' => 'required|string|max:255',
          // 'addresses.*.email' => 'nullable|email|max:255',
          'addresses.*.phone' => 'required|string|max:20',
          'addresses.*.address' => 'required|string',
          'addresses.*.city' => 'required|string|max:100',
          'addresses.*.region' => 'nullable|string|max:100',
          // 'addresses.*.country_id' => 'required|exists:countries,id',
          // 'addresses.*.zone_id' => 'nullable|exists:zones,id',
          'addresses.*.zipcode' => 'nullable|string|max:20',

          // customer 
          'customer.full_name' => 'nullable|string|max:255',
          'customer.email' => 'nullable|email|max:255',
          'customer.phone' => 'required|string|max:20',
          'customer.customer_id' => 'nullable|string|max:20',
          'customer.alt_phone' => 'nullable|string|max:20',
          'customer.address' => 'nullable|string',
          'customer.city' => 'nullable|string|max:100',
          'customer.region' => 'nullable|string|max:100',
          'customer.country_id' => 'nullable|exists:countries,id',
          'customer.zone_id' => 'nullable|exists:zones,id',
          'customer.zipcode' => 'nullable|string|max:20',
          'customer.is_spam' => 'boolean',
       ];
    }
    public function messages()
    {
       return [
          'order_no.required' => 'Order number is required.',
          'order_no.unique' => 'Order number must be unique.',
          'warehouse_id.required' => 'Warehouse is required.',
          'warehouse_id.exists' => 'The selected warehouse is invalid.',
          'country_id.required' => 'Country is required.',
          'country_id.exists' => 'The selected country is invalid.',
          'vendor_id.exists' => 'The selected vendor is invalid.',
          'status_id.required' => 'Status ID is required.',
          'status_id.integer' => 'Status ID must be an integer.',
          'delivery_status_id.integer' => 'Delivery Status ID must be an integer.',
          'paid.boolean' => 'Paid must be true or false.',
          'payment_id.exists' => 'The selected payment method is invalid.',
          // 'sub_total.required' => 'Sub total is required.',
          // 'sub_total.numeric' => 'Sub total must be a number.',
          // 'total_price.required' => 'Total price is required.',
          // 'total_price.numeric' => 'Total price must be a number.',
          // 'discount.numeric' => 'Discount must be a number.',
          'shipping_charges.numeric' => 'Shipping charges must be a number.',
          // 'currency.required' => 'Currency is required.',
          // 'currency.max' => 'Currency must not exceed 3 characters.',
          // 'weight.numeric' => 'Weight must be a number.',
          // 'platform.max' => 'Platform must not exceed 50 characters.',
          // 'source.max' => 'Source must not exceed 50 characters.',
          // 'distance.numeric' => 'Distance must be a number.',

          // Nested relationship messages
          'order_items.array' => 'Order items must be an array.',
          'order_items.*.product_id.required' => 'Product ID is required for each order item.',
          'order_items.*.product_id.exists' => 'The selected product ID for an order item is invalid.',
          'order_items.*.unit_price.required' => 'Unit price is required for each order item.',
          'order_items.*.unit_price.numeric' => 'Unit price for each order item must be a number.',
          'order_items.*.quantity.required' => 'Quantity is required for each order item.',
          'order_items.*.quantity.integer' => 'Quantity for each order item must be an integer.',
          // 'order_items.*.total.required' => 'Total is required for each order item.',
          // 'order_items.*.total.numeric' => 'Total for each order item must be a number.',
          // 'order_items.*.currency.required' => 'Currency is required for each order item.',
          // 'order_items.*.currency.max' => 'Currency for each order item must not         exceed 3 characters.',
            'addresses.array' => 'Addresses must be an array.', 
            'addresses.*.type.required' => 'Address type is required for each address.',
            'addresses.*.type.in' => 'Address type must be one of: shipping, pickup, billing, return, drop.',
            'addresses.*.full_name.required' => 'Full name is required for each address.',
            'addresses.*.phone.required' => 'Phone is required for each address.',
            'addresses.*.address.required' => 'Address is required for each address.',
            'addresses.*.city.required' => 'City is required for each address.',    
            // 'addresses.*.country_id.required' => 'Country is required for each address.',
            // 'addresses.*.country_id.exists' => 'The selected country for an address is invalid.',
            // 'addresses.*.zone_id.exists' => 'The selected zone for an address is invalid.',          
            'customer.full_name.max' => 'Customer full name must not exceed 255 characters.',
            'customer.email.email' => 'Customer email must be a valid email address.',
            'customer.email.max' => 'Customer email must not exceed 255 characters.',
            'customer.phone.required' => 'Customer phone is required.',
            'customer.phone.max' => 'Customer phone must not exceed 20 characters.',
            'customer.customer_id.max' => 'Customer ID must not exceed 20 characters.',         
            'customer.alt_phone.max' => 'Customer alternate phone must not exceed 20 characters.',
            'customer.city.max' => 'Customer city must not exceed 100 characters.',
            'customer.region.max' => 'Customer region must not exceed 100 characters.', 
            'customer.country_id.exists' => 'The selected customer country is invalid.',    
            'customer.zone_id.exists' => 'The selected customer zone is invalid.',
            'customer.zipcode.max' => 'Customer zipcode must not exceed 20 characters.',
            'customer.is_spam.boolean' => 'Customer is_spam must be true or false.',
       ];
    }
}