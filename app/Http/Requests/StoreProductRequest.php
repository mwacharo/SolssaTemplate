<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'product_name' => 'required|string|max:255',
            'sku_no' => 'required|string|max:100|unique:products,sku_no',
            'country_specific_sku' => 'nullable|string|max:100',
            'bar_code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'country_id' => 'required|integer|exists:countries,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'user_id' => 'required|integer|exists:users,id',
            'product_type' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'value' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'brand' => 'nullable|string|max:100',
            'product_link' => 'nullable|url|max:255',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'nullable|url|max:255',
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'nullable|url|max:255',
            'active' => 'required|boolean',
            'stock_management' => 'required|boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'tracking_required' => 'required|boolean',
            'fragile' => 'required|boolean',
            'hazardous' => 'required|boolean',
            'temperature_sensitive' => 'required|boolean',
            'returnable' => 'required|boolean',
            'packaging_type' => 'nullable|string|max:100',
            'handling_instructions' => 'nullable|string',
            'delivery_time_window' => 'nullable|string|max:100',
            'customs_info' => 'nullable|string',
            'insurance_value' => 'nullable|numeric|min:0',
            'ratings' => 'nullable|numeric|min:0|max:5',
            'reviews' => 'nullable|array',
            'reviews.*' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:50',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'update_comment' => 'nullable|string|max:255',
        ];
    }
}
