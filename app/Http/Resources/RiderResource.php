<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderResource extends JsonResource
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
            'user_id'        => $this->user_id,
            'country_id'     => $this->country_id,
            'name'           => $this->name,
            'email'          => $this->email,
            'address'        => $this->address,
            'city'           => $this->city,
            'state'          => $this->state,
            'vehicle_number' => $this->vehicle_number,
            'license_number' => $this->license_number,
            'phone'          => $this->phone,
            'status'         => $this->status,
        ];
    }
}
