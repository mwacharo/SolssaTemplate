<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IvrOption extends Model
{
    /** @use HasFactor
     * y<\Database\Factories\IvrOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'option_number',
        'description',
        'forward_number',
        'phone_number',
        'status',
        'branch_id',
        'country_id',
    ];
}
