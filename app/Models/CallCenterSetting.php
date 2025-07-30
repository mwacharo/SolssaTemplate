<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallCenterSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'username',
        'api_key',
        'phone',
        'sandbox',
        'default_voice',
        'timeout',
        'recording_enabled',
        'welcome_message',
        'no_input_message',
        'invalid_option_message',
        'connecting_agent_message',
        'agents_busy_message',
        'voicemail_prompt',
        'callback_url',
        'event_callback_url',
        'ringback_tone',
        'voicemail_callback',
        'fallback_number',
        'default_forward_number',
        'debug_mode',
        'log_level',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
