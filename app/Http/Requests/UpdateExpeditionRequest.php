<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpeditionRequest extends FormRequest
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
        $expeditionId = $this->route('expedition'); // from route model binding

        return [
            'source_country'   => 'sometimes|string|max:255',
            'destination'      => 'sometimes|string|max:255',

            'shipment_date'    => 'sometimes|date',
            'arrival_date'     => 'sometimes|date|after_or_equal:shipment_date',

            'transporter_name' => 'sometimes|string|max:255',

            'tracking_number'  => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('expeditions', 'tracking_number')->ignore($expeditionId),
            ],

            'packages_number'  => 'sometimes|integer|min:1',
            'weight'           => 'sometimes|numeric|min:0',
            'shipment_fees'    => 'sometimes|numeric|min:0',

            'vendor_id'        => 'nullable|integer|exists:vendors,id',

            // Shipment items (optional but validated if present)
            'shipment_items'                     => 'sometimes|array|min:1',
            'shipment_items.*.product'           => 'required_with:shipment_items|array',
            'shipment_items.*.product.name'      => 'required_with:shipment_items|string|max:255',
            'shipment_items.*.product.sku'       => 'required_with:shipment_items|string|max:255',
            'shipment_items.*.quantity_sent'     => 'required_with:shipment_items|integer|min:1',
        ];
    }
}
