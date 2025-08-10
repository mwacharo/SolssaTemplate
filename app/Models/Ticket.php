<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'contact_id', 
        'vendor_id',
        'assigned_user_id',
        'client_name',
        'client_phone',
        'client_email',
        'subject',
        'description',
        'priority',
        'category',
        'status',
        'call_duration'
    ];

    protected $casts = [
        'call_duration' => 'integer',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    // Scopes
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    // Accessors
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium', 
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Medium'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => 'Open'
        };
    }

    // Constants for dropdown options
    const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High', 
        'urgent' => 'Urgent'
    ];

    const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'pending' => 'Pending',
        'resolved' => 'Resolved',
        'closed' => 'Closed'
    ];

    const CATEGORIES = [
        'general' => 'General',
        'technical' => 'Technical',
        'billing' => 'Billing',
        'support' => 'Support',
        'feature_request' => 'Feature Request',
        'bug_report' => 'Bug Report'
    ];
}