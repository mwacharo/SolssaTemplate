<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplate extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'subject'      => $this->subject,
            'body'         => $this->body,
            'placeholders' => $this->placeholders,
            'created_at'   => $this->created_at?->toDateTimeString(),
            'updated_at'   => $this->updated_at?->toDateTimeString(),
        ];
    }
}
