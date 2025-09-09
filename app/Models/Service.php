<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;


     protected $fillable = ['service_name', 'description', 'is_active'];

    public function conditions()
    {
        return $this->hasMany(ServiceCondition::class);
    }

    public function vendorServices()
    {
        return $this->hasMany(VendorService::class);
    }
}
