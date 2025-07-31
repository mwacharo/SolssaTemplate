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
            'product_name' => 'sometimes|string|max:255',
            'sku_no' => 'sometimes|string|max:100',
            'country_specific_sku' => 'sometimes|string|max:100|nullable',
            'bar_code' => 'sometimes|string|max:100|nullable',
            'description' => 'sometimes|string|nullable',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'vendor_id' => 'sometimes|integer|exists:vendors,id',
            'country_id' => 'sometimes|integer|exists:countries,id',
            'product_variant_id' => 'sometimes|integer|exists:product_variants,id|nullable',
            'user_id' => 'sometimes|integer|exists:users,id',
            'product_type' => 'sometimes|string|max:100|nullable',
            'weight' => 'sometimes|numeric|min:0|nullable',
            'length' => 'sometimes|numeric|min:0|nullable',
            'width' => 'sometimes|numeric|min:0|nullable',
            'height' => 'sometimes|numeric|min:0|nullable',
            'value' => 'sometimes|numeric|min:0|nullable',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'sometimes|numeric|min:0|nullable',
            'tax_rate' => 'sometimes|numeric|min:0|max:100|nullable',
            'brand' => 'sometimes|string|max:100|nullable',
            'product_link' => 'sometimes|url|nullable',
            'image_urls' => 'sometimes|array|nullable',
            'image_urls.*' => 'string|url|nullable',
            'video_urls' => 'sometimes|array|nullable',
            'video_urls.*' => 'string|url|nullable',
            'active' => 'sometimes|boolean',
            'stock_management' => 'sometimes|boolean',
            'stock_quantity' => 'sometimes|integer|min:0|nullable',
            'tracking_required' => 'sometimes|boolean',
            'fragile' => 'sometimes|boolean',
            'hazardous' => 'sometimes|boolean',
            'temperature_sensitive' => 'sometimes|boolean',
            'returnable' => 'sometimes|boolean',
            'packaging_type' => 'sometimes|string|max:100|nullable',
            'handling_instructions' => 'sometimes|string|nullable',
            'delivery_time_window' => 'sometimes|string|max:100|nullable',
            'customs_info' => 'sometimes|string|nullable',
            'insurance_value' => 'sometimes|numeric|min:0|nullable',
            'ratings' => 'sometimes|numeric|min:0|max:5|nullable',
            'reviews' => 'sometimes|array|nullable',
            'reviews.*' => 'string|nullable',
            'tags' => 'sometimes|array|nullable',
            'tags.*' => 'string|max:50|nullable',
            'slug' => 'sometimes|string|max:255|nullable',
            'meta_title' => 'sometimes|string|max:255|nullable',
            'meta_description' => 'sometimes|string|nullable',
            'update_comment' => 'sometimes|string|nullable',
        ];
    }
}
