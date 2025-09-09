<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCondition extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceConditionFactory> */
    use HasFactory;


      protected $fillable = ['service_id', 'condition_type', 'min_value', 'max_value', 'rate', 'unit'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceRates()
    {
        return $this->hasMany(ServiceRate::class);
    }
}
