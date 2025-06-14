<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email'      => $this->email,
            'roles' => $this->roles->pluck('name'), // Collection of role names
            'permissions' => $this->getAllPermissions()->pluck('name'), // All permissions (direct + via roles)
            'status' => $this->status ?? 'Active', // If you have a 'status' column
            'created_at' => $this->created_at?->toDateString(),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
   
}
