<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'country_id' => $this->country_id,
            'country'    => $this->country, // Assumes relation or attribute
            // 'state_id' => $this->state_id, // Uncomment if needed
            'city_id'    => $this->city_id,
            'city'       => $this->city, // Assumes relation or attribute
            'latitude'   => $this->latitude,
            'longitude'  => $this->longitude,
            'population' => $this->population,
        ];
    }
}
