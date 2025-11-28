<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpeditionRequest extends FormRequest
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
            'source_country'   => 'required|string|max:255',
            // 'destination'      => 'required|string|max:255',
            'warehouse_id'     => 'required|integer|exists:warehouses,id',

            'shipment_date'    => 'required|date',
            'arrival_date'     => 'required|date|after_or_equal:shipment_date',

            'transporter_name' => 'required|string|max:255',
            'tracking_number'  => 'required|string|max:255|unique:expeditions,tracking_number',

            'packages_number'  => 'required|integer|min:1',
            'weight'           => 'required|numeric|min:0',
            'shipment_fees'    => 'required|numeric|min:0',

            'vendor_id'        => 'nullable|integer|exists:users,id',

            'shipment_items'                     => 'required|array|min:1',
            'shipment_items.*.product'           => 'required|array',
            'shipment_items.*.product.name'      => 'required|string|max:255',
            'shipment_items.*.product.sku'       => 'required|string|max:255',
            'shipment_items.*.quantity_sent'     => 'required|integer|min:1',
        ];
    }
}
