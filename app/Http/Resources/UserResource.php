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
         'phone_number'      => $this->phone_number,
         'username'   => $this->username,
         'client_name' => $this->client_name,
         'roles' => $this->roles->pluck('name'), // Collection of role names
         'permissions' => $this->getAllPermissions()->pluck('name'), // All permissions (direct + via roles)
         'status' => $this->status ?? 'Active', // If you have a 'status' column



         'country' => $this->whenLoaded('country'),

         'created_at' => $this->created_at?->toDateString(),
         'updated_at' => $this->updated_at?->toDateString(),
      ];
   }


   //  public function toArray(Request $request): array
   //  {
   //      $authUser = $request->user();

   //      return [
   //          'id' => $this->id,
   //          'name' => $this->name,

   //          'email' => $this->when(
   //              $this->canViewEmail($authUser),
   //              $this->email
   //          ),

   //          'phone_number' => $this->when(
   //              $this->canViewPhone($authUser),
   //              $this->phone_number
   //          ),
   //      ];
   //  }



   // public function toArray(Request $request): array
   // {
   //    $authUser = $request->user();

   //    $data = [
   //       'id'         => $this->id,
   //       'name'       => $this->name,
   //       'username'   => $this->username,
   //       'client_name' => $this->client_name,
   //       'roles'      => $this->roles->pluck('name'),
   //       'permissions' => $this->getAllPermissions()->pluck('name'),
   //       'status'     => $this->status ?? 'Active',
   //       'country'    => $this->whenLoaded('country'),
   //       'created_at' => $this->created_at?->toDateString(),
   //       'updated_at' => $this->updated_at?->toDateString(),
   //    ];

   //    // ✅ Only allow sensitive data for authorized users
   //    if ($authUser && $authUser->hasPermissionTo('users_view_sensitive')) {
   //       $data['email'] = $this->email;
   //       $data['phone_number'] = $this->phone_number;
   //    }

   //    return $data;
   // }



   // 🔐 place them here
   protected function canViewEmail($authUser): bool
   {
      return $authUser->hasPermissionTo('view user email')
         || $authUser->id === $this->id;
   }

   protected function canViewPhone($authUser): bool
   {
      return $authUser->hasPermissionTo('view user phone')
         || $authUser->id === $this->id;
   }
}
