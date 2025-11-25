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
  $rules = [
    'order_no' => 'required|unique:orders,order_no',
    'reference' => 'nullable|string|max:255',
    'warehouse_id' => 'required|exists:warehouses,id',
    'country_id' => 'nullable|exists:countries,id',
    'vendor_id' => 'nullable|exists:users,id',
    'status_id' => 'required|integer',
    'delivery_status_id' => 'nullable|integer',
    'paid' => 'boolean',
    'payment_id' => 'nullable|exists:payments,id',
    'shipping_charges' => 'nullable|numeric|min:0',
    'weight' => 'nullable|numeric|min:0',
    'platform' => 'nullable|string|max:50',
    'source' => 'nullable|string|max:50',
    'distance' => 'nullable|numeric|min:0',
    'delivery_date' => 'nullable|date',
    'recall_date' => 'nullable|date',
    'customer_notes' => 'nullable|string',

    // For nested relationships
    'order_items' => 'required|array|min:1',
    'order_items.*.product_id' => 'nullable|exists:products,id',
    'order_items.*.sku' => 'required|string|max:100',
    'order_items.*.unit_price' => 'required|numeric|min:0',
    'order_items.*.quantity' => 'required|integer|min:1',

    // Addresses can be pickup/drop or customer, all nullable
    'addresses' => 'nullable|array',
    'addresses.*.type' => 'nullable|in:shipping,pickup,billing,return,drop',
    'addresses.*.full_name' => 'nullable|string|max:255',
    'addresses.*.email' => 'nullable|email|max:255',
    'addresses.*.phone' => 'nullable|string|max:20',
    'addresses.*.address' => 'nullable|string',
    'addresses.*.city_id' => 'nullable|exists:cities,id',
    'addresses.*.region' => 'nullable|string|max:100',
    'addresses.*.zone_id' => 'nullable|exists:zones,id',
    'addresses.*.zipcode' => 'nullable|string|max:20',

    // customer address is nullable
    'customer.full_name' => 'nullable|string|max:255',
    'customer.email' => 'nullable|email|max:255',
    'customer.phone' => 'nullable|string|max:20',
    'customer.customer_id' => 'nullable|string|max:20',
    'customer.alt_phone' => 'nullable|string|max:20',
    'customer.address' => 'nullable|string',
    'customer.shipping_address' => 'nullable|string',
    'customer.city_id' => 'nullable|exists:cities,id',
    'customer.region' => 'nullable|string|max:100',
    'customer.country_id' => 'nullable|exists:countries,id',
    'customer.zone_id' => 'nullable|exists:zones,id',
    'customer.zipcode' => 'nullable|string|max:20',
    'customer.is_spam' => 'boolean',

    // Required fields for this request type
    'total_price' => 'required|numeric|min:0',
    'sub_total' => 'required|numeric|min:0',
    'amount_paid' => 'required|numeric|min:0',

    // pickup_address and dropoff_address as optional arrays
    'pickup_address' => 'nullable|array',
    'pickup_address.full_name' => 'nullable|string|max:255',
    'pickup_address.phone' => 'nullable|string|max:20',
    'pickup_address.city_id' => 'nullable|exists:cities,id',
    'pickup_address.zone_id' => 'nullable|exists:zones,id',
    'pickup_address.address' => 'nullable|string',
    'pickup_address.region' => 'nullable|string|max:100',
    'pickup_address.zipcode' => 'nullable|string|max:20',
    'pickup_address.email' => 'nullable|email|max:255',

    'dropoff_address' => 'nullable|array',
    'dropoff_address.full_name' => 'nullable|string|max:255',
    'dropoff_address.phone' => 'nullable|string|max:20',
    'dropoff_address.city_id' => 'nullable|exists:cities,id',
    'dropoff_address.zone_id' => 'nullable|exists:zones,id',
    'dropoff_address.address' => 'nullable|string',
    'dropoff_address.region' => 'nullable|string|max:100',
    'dropoff_address.zipcode' => 'nullable|string|max:20',
    'dropoff_address.email' => 'nullable|email|max:255',

    // from_warehouse_id and to_warehouse_id as nullable
    'from_warehouse_id' => 'nullable|exists:warehouses,id',
    'to_warehouse_id' => 'nullable|exists:warehouses,id',

    // from_address and to_address as optional arrays
    'from_address' => 'nullable|array',
    'from_address.city' => 'nullable|string|max:100',
    'to_address' => 'nullable|array',
    'to_address.city' => 'nullable|string|max:100',
  ];


  // If addresses contain both pickup and shipping/drop, customer.phone is not required
  $addresses = $this->input('addresses', []);
  $hasPickup = false;
  $hasDrop = false;

  foreach ($addresses as $address) {
    if (isset($address['type']) && $address['type'] === 'pickup') {
      $hasPickup = true;
    }
    if (isset($address['type']) && in_array($address['type'], ['drop', 'shipping'])) {
      $hasDrop = true;
    }
  }

  if ($hasPickup && $hasDrop) {
    // Remove required from customer.phone
    $rules['customer.phone'] = 'nullable|string|max:20';
  }

  return $rules;
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
    'delivery_date.date' => 'Delivery date must be a valid date.',
    'recall_date.date' => 'Recall date must be a valid date.',
    'customer_notes.string' => 'Customer notes must be a string.',
    'total_price.required' => 'Total price is required.',
    'total_price.numeric' => 'Total price must be a number.',
    'sub_total.required' => 'Sub total is required.',
    'sub_total.numeric' => 'Sub total must be a number.',
    'amount_paid.required' => 'Amount paid is required.',
    'amount_paid.numeric' => 'Amount paid must be a number.',
    'shipping_charges.numeric' => 'Shipping charges must be a number.',
    'order_items.required' => 'Order items are required.',
    'order_items.array' => 'Order items must be an array.',
    'order_items.*.sku.required' => 'SKU is required for each order item.',
    'order_items.*.sku.max' => 'SKU must not exceed 100 characters.',
    'order_items.*.product_id.exists' => 'The selected product ID for an order item is invalid.',
    'order_items.*.unit_price.required' => 'Unit price is required for each order item.',
    'order_items.*.unit_price.numeric' => 'Unit price for each order item must be a number.',
    'order_items.*.quantity.required' => 'Quantity is required for each order item.',
    'order_items.*.quantity.integer' => 'Quantity for each order item must be an integer.',
    'addresses.required' => 'Addresses are required.',
    'addresses.array' => 'Addresses must be an array.',
    'addresses.*.type.required' => 'Address type is required for each address.',
    'addresses.*.type.in' => 'Address type must be one of: shipping, pickup, billing, return, drop.',
    'addresses.*.full_name.required' => 'Full name is required for each address.',
    'addresses.*.phone.required' => 'Phone is required for each address.',
    'addresses.*.address.required' => 'Address is required for each address.',
    'addresses.*.city.required' => 'City is required for each address.',
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
