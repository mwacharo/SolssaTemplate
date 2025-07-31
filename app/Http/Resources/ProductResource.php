<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'sku_no' => $this->sku_no,
            'country_specific_sku' => $this->country_specific_sku,
            'bar_code' => $this->bar_code,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'vendor_id' => $this->vendor_id,
            'country_id' => $this->country_id,
            'product_variant_id' => $this->product_variant_id,
            'user_id' => $this->user_id,
            'product_type' => $this->product_type,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'value' => $this->value,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'tax_rate' => $this->tax_rate,
            'brand' => $this->brand,
            'product_link' => $this->product_link,
            'image_urls' => $this->image_urls,
            'video_urls' => $this->video_urls,
            'active' => $this->active,
            'stock_management' => $this->stock_management,
            'stock_quantity' => $this->stock_quantity,
            'tracking_required' => $this->tracking_required,
            'fragile' => $this->fragile,
            'hazardous' => $this->hazardous,
            'temperature_sensitive' => $this->temperature_sensitive,
            'returnable' => $this->returnable,
            'packaging_type' => $this->packaging_type,
            'handling_instructions' => $this->handling_instructions,
            'delivery_time_window' => $this->delivery_time_window,
            'customs_info' => $this->customs_info,
            'insurance_value' => $this->insurance_value,
            'ratings' => $this->ratings,
            'reviews' => $this->reviews,
            'tags' => $this->tags,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'update_comment' => $this->update_comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
