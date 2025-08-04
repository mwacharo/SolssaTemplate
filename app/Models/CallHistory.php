<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallHistory extends Model
{
    use SoftDeletes;

    protected $table = 'call_histories';

    protected $fillable = [
        'isActive',
        'direction',
        'sessionId',
        'callerNumber',
        'callerCarrierName',
        'clientDialedNumber',
        'callerCountryCode',
        'destinationNumber',
        'durationInSeconds',
        'currencyCode',
        'recordingUrl',
        'amount',
        'hangupCause',
        'user_id',
        'ivr_option_id',
        'orderNo',
        'notes',
        'nextCallStep',
        'conference',
        'status',
        'lastBridgeHangupCause',
        'callStartTime',
        'callSessionState',
    ];

    protected $dates = [
        'callStartTime',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    // add relationship with User model

    // Relationship with User model (agent)
    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with IVR Option model
    public function ivrOption()
    {
        return $this->belongsTo(IvrOption::class, 'ivr_option_id');
    }
}
