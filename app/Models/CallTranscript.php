<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallTranscript extends Model
{
    /** @use HasFactory<\Database\Factories\CallTranscriptFactory> */
    use HasFactory;
    use SoftDeletes;



     protected $fillable = [
        'call_id','user_id','recording_url','transcript',
        'sentiment','fulfillment_score','cs_rating','analysis','processed_at'
    ];

    protected $casts = [
        'analysis' => 'array',
        'processed_at' => 'datetime',
    ];
}
