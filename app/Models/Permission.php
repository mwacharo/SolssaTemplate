<?php

namespace App\Models;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Contracts\Permission as PermissionContract;

use Spatie\Activitylog\LogOptions;

class Permission extends SpatiePermission implements PermissionContract
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('permission')
            ->logOnly(['name'])
            ->logOnlyDirty();
    }


    protected $fillable = [
        'name',
        'guard_name',
        'description',    // Your custom field
        'active',         // Your custom field
        'color',          // Your custom field
        'icon',           // Your custom field
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($permission) {
            // Set default values when creating
            $permission->active = $permission->active ?? true;
            $permission->color = $permission->color ?? 'green';
            $permission->icon = $permission->icon ?? 'mdi-key';
        });
    }

    /**
     * Scope to get only active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get inactive permissions.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Check if the permission is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get formatted description or default text
     */
    public function getFormattedDescriptionAttribute(): string
    {
        return $this->description ?? 'No description provided';
    }

    /**
     * Get permission display name with description
     */
    public function getDisplayTextAttribute(): string
    {
        $text = $this->name;
        if ($this->description) {
            $text .= ' - ' . $this->description;
        }
        return $text;
    }

    /**
     * Get permission badge HTML (useful for display)
     */
    public function getBadgeHtmlAttribute(): string
    {
        return "<span class='badge' style='background-color: {$this->color}; color: white;'>
                    <i class='{$this->icon}'></i> {$this->name}
                </span>";
    }
}
