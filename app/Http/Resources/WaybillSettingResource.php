<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaybillSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'country_id'    => $this->country_id,
            'template_name' => $this->template_name,
            'options'       => $this->options,
            'name'          => $this->name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'address'       => $this->address,
            'terms'         => $this->terms,
            'footer'        => $this->footer,
        ];
    }
}
