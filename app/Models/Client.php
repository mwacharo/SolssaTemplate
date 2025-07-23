<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country_id',
        'branch_id',
        'notes',
        'vendor_id',
        'status',
        'user_id',
        'phone_number'
    ];
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];



    // public function calls()
    // {
    //     return $this->hasMany(CallHistory::class);
    // }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }   


    //client has messages 


    public function messages()
    {
        return Message::where(function ($query) {
            $query->where('to', $this->phone_number)
                  ->orWhere('from', $this->phone_number);
        });
    }
}
