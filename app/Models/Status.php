<?php

namespace App\Models;

use App\Traits\BelongsToUserAndCountry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    /** @use HasFactory<\Database\Factories\StatusFactory> */
    use HasFactory;
    use BelongsToUserAndCountry;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'status_category',
        'description',
        'color',
        'country_id',
    ];
}
