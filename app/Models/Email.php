<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    /** @use HasFactory<\Database\Factories\EmailFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * Optional if table name matches plural snake_case of the class name.
     */
    protected $table = 'emails';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'to',
        "from",
        'subject',
        'body',
        'status',       // draft, sent, failed, scheduled
        'sent_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the client this email belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only drafts.
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only sent emails.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Mark the email as sent and set the sent_at timestamp.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
