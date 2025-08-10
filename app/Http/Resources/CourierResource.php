<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            // 'vehicle_type' => $this->vehicle_type,
            // 'license_plate' => $this->license_plate,
            'status' => $this->status,
            'country_id' => $this->country_id,
            // 'city_id' => $this->city_id,
            // 'zone_id' => $this->zone_id,
        ];
    }
}
