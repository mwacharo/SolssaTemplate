<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaybillSetting extends Model
{
    /** @use HasFactory<\Database\Factories\WaybillSettingFactory> */


    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'template_name',
        'options',
        'name',
        'phone',
        'email',
        'address',
        'terms',
        'footer',
        'logo_path',
    ];


     protected $casts = [
        'options' => 'array',
    ];

    /**
     * Get the country that owns the waybill setting.
     */
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
