<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCondition extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceConditionFactory> */
    use HasFactory;


    protected $fillable = [
        'service_id',
        'condition_type_id',
        'min_value',
        'max_value',
        'operator',
        'rate',
        'rate_type',
        'value',
        'unit',
        'priority',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceRates()
    {
        return $this->hasMany(ServiceRate::class);
    }

    public function conditionType()
    {
        return $this->belongsTo(ConditionType::class);
    }
}
