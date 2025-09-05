<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'product_name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|nullable',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'vendor_id' => 'sometimes|integer|exists:vendors,id',
            'country_id' => 'sometimes|integer|exists:countries,id|nullable',
            'update_comment' => 'sometimes|string|nullable',

            // Prices
            'prices' => 'sometimes|array',
            'prices.*.id' => 'sometimes|integer',
            'prices.*.product_id' => 'sometimes|integer|exists:products,id',
            'prices.*.vendor_id' => 'sometimes|integer|exists:vendors,id',
            'prices.*.base_price' => 'sometimes|numeric|min:0',
            'prices.*.discount_price' => 'sometimes|numeric|min:0|nullable',
            'prices.*.cost_price' => 'sometimes|numeric|min:0|nullable',
            'prices.*.wholesale_price' => 'sometimes|numeric|min:0|nullable',
            'prices.*.currency' => 'sometimes|string|max:10',
            'prices.*.valid_from' => 'sometimes|date|nullable',
            'prices.*.valid_to' => 'sometimes|date|nullable',
            'prices.*.is_active' => 'sometimes|boolean',

            // Stocks
            'stocks' => 'sometimes|array',
            'stocks.*.id' => 'sometimes|integer',
            'stocks.*.product_id' => 'sometimes|integer|exists:products,id',
            'stocks.*.warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'stocks.*.current_stock' => 'sometimes|integer|min:0',
            'stocks.*.committed_stock' => 'sometimes|integer|min:0',
            'stocks.*.defected_stock' => 'sometimes|integer|min:0',
            'stocks.*.historical_stock' => 'sometimes|integer|min:0',
            'stocks.*.stock_threshold' => 'sometimes|integer|min:0',
            'stocks.*.batch_no' => 'sometimes|string|nullable',
            'stocks.*.expiry_date' => 'sometimes|date|nullable',

            // Media
            'media' => 'sometimes|array',
            'media.*.id' => 'sometimes|integer',
            'media.*.product_id' => 'sometimes|integer|exists:products,id',
            'media.*.media_type' => 'sometimes|string|nullable',
            'media.*.url' => 'required_with:media|string|url',
            'media.*.alt_text' => 'sometimes|string|nullable',
            'media.*.is_primary' => 'sometimes|boolean',
            'media.*.position' => 'sometimes|integer|min:0',

            // Variants
            'variants' => 'sometimes|array',

         
        ];
    }
}
