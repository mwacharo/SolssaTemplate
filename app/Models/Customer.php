<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use SoftDeletes;
    use HasFactory;
    use LogsActivity;

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('customer');
    }

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'alt_phone',
        'address',
        'city_id',
        'region',
        'country_id',
        'vendor_id',
        'zone_id',
        'zipcode',
        'is_spam',
    ];

    /**
     * Get the orders for the customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the country associated with the customer.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the vendor associated with the customer.
     */
    // public function vendor()
    // {
    //     return $this->belongsTo(Vendor::class);
    // }


    public function vendor()
{
    return $this->belongsTo(\App\Models\User::class, 'vendor_id');
}


    /**
     * Get the zone associated with the customer.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
