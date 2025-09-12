<?php

namespace App\Models;

use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;  

use App\Traits\BelongsToUserAndCountry;




class Order extends Model
{
    use SoftDeletes;
    use HasFactory;
    use LogsActivity;

        use BelongsToUserAndCountry;


    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('order');
    }


    protected $fillable = [
        'order_no',
        'custommer_notes',
        'reference',
        'client_id',
        'customer_id',
        'warehouse_id',
        'country_id',
        'vendor_id',
        'country_id',
        'currency',
        'agent_id',
        // 'user_id',
        'rider_id',
        'zone_id',
        'status',
        'delivery_status',
        'delivery_date',
        'schedule_date',
        'paid',
        'payment_method',
        'payment_id',
        'sub_total',
        'total_price',
        'discount',
        'shipping_charges',
        'currency',
        // 'weight',
        'platform',
        'source',
        'pickup_address',
        'pickup_city',
        'pickup_phone',
        'pickup_shop',
        'latitude',
        'longitude',
        'distance',
        'customer_notes',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'delivery_date' => 'datetime',
        'schedule_date' => 'datetime',
        'sub_total' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_charges' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }


    
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }


    // public function items() { return $this->hasMany(OrderItem::class); }
    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }
    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }
    public function pickupAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'pickup');
    }
    public function assignments()
    {
        return $this->hasMany(OrderAssignment::class);
    }
    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }


    // public function  replacementOrders()
    // {
    //     return $this->hasMany(Order::class, 'replacement_for_order_id');
    // }
    // public function refunds()
    // {
    //     return $this->hasMany(Refund::class);
    // }
    // public function events() { return $this->hasMany(OrderEvent::class); }
    // public function remittances()
    // {
    //     return $this->hasMany(Remittance::class);
    // }
    // public function client() { return $this->belongsTo(User::class,'client_id'); }
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function statusTimestamps()
    {
        return $this->hasOne(OrderStatusTimestamp::class);
    }

    public function callLogs()
{
    return $this->hasMany(CallHistory::class);
}
}
