<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusCategory extends Model
{
    /** @use HasFactory<\Database\Factories\StatusCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'status_id',
    ];
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
