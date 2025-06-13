<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'alt_phone',
        'address',
        'country_name',
        'state_name',
        'city',
        'zip_code',
        'type',
        'company_name',
        'job_title',
        'whatsapp',
        'linkedin',
        'telegram',
        'facebook',
        'twitter',
        'instagram',
        'wechat',
        'snapchat',
        'tiktok',
        'youtube',
        'pinterest',
        'reddit',
        'consent_to_contact',
        'consent_given_at',
        'tags',
        'profile_picture',
        'notes',
        'status',
        'contactable_id',
        'contactable_type', // Polymorphic columns
    ];

    public function contactable()
    {
        return $this->morphTo();
    }
}
