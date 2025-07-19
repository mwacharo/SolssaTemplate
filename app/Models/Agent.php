<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    /** @use HasFactory<\Database\Factories\AgentFactory> */
    use HasFactory;
    use SoftDeletes;



    protected $fillable = [
        'user_id',
        'country_id',
        'name',
        'email',
        'address',
        'city',
        'state',
        'phone',
        'status',
    ];



    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
