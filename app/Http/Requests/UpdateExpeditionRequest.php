<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpeditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*--------------------------------------------------------------
             | Expedition Fields
             --------------------------------------------------------------*/
            'source_country'   => 'sometimes|string|max:255',
            'destination'      => 'sometimes|string|max:255',

            'shipment_date'    => 'sometimes|date',
            'arrival_date'     => 'sometimes|date|after_or_equal:shipment_date',

            'transporter_name' => 'sometimes|string|max:255',
            'tracking_number'  => 'sometimes|string|max:255',

            'packages_number'  => 'sometimes|integer|min:1',
            'weight'           => 'sometimes|numeric|min:0',
            'shipment_fees'    => 'sometimes|numeric|min:0',

            'vendor_id'        => 'nullable|integer|exists:users,id',


            /*--------------------------------------------------------------
             | Shipment Items
             | Items are optional, but if sent — must be valid.
             --------------------------------------------------------------*/
            'shipment_items'                     => 'sometimes|array',
            'shipment_items.*.id'                => 'sometimes|integer',

            // Allow EITHER product_id OR product.id
            'shipment_items.*.product_id'        => 'sometimes|integer|min:1',
            'shipment_items.*.product'           => 'sometimes|array',
            'shipment_items.*.product.id'        => 'sometimes|integer|min:1',

            'shipment_items.*.quantity_sent'     => 'required_with:shipment_items|integer|min:1',
        ];
    }

    /**
     * Sanitize validated data and prevent primary key overwrite.
     */
    public function validated($key = null, $default = null)
    {
        return collect(parent::validated())
            ->except(['id']) // NEVER allow overwriting primary key
            ->toArray();
    }
}
