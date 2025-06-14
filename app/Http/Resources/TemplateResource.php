<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     return parent::toArray($request);
    // }



    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'channel'      => $this->channel,
            'module'       => $this->module,
            'content'      => $this->content,
            'placeholders' => $this->placeholders, // Already JSON, no need to decode
            'owner_type'   => $this->owner_type,
            'owner_id'     => $this->owner_id,
            'active'       => $this->active,
            'created_at'   => $this->created_at?->toDateTimeString(),
            'updated_at'   => $this->updated_at?->toDateTimeString(),
        ];
    }
}
