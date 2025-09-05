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
            'sku' => $this->sku,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'vendor_id' => $this->vendor_id,
            'country_id' => $this->country_id,
            'update_comment' => $this->update_comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,


            'vendor' => [
                'id' => optional($this->vendor)->id,
                'name' => optional($this->vendor)->name,
            ],
            'country'  => new CountryResource($this->whenLoaded('country')),

            // Direct relationship data
            // 'vendor'     => $this->whenLoaded('vendor'),
            // 'category'   => $this->whenLoaded('category'),
            'country'    => $this->whenLoaded('country'),
            'prices'     => $this->whenLoaded('prices'),
            'stocks'     => $this->whenLoaded('stocks'),
            'media'      => $this->whenLoaded('media'),
            'variants'   => $this->whenLoaded('variants'),
            'statistics' => $this->whenLoaded('statistics'),
            'offers'     => $this->whenLoaded('offers'),
            'images'     => $this->whenLoaded('images'),    ];
    }
}
