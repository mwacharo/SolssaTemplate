<?php

namespace App\Models;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Activitylog\LogOptions;


class Role extends SpatieRole implements RoleContract
{



     use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('role')
            ->logOnly(['name'])
            ->logOnlyDirty();
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'active',
        'color',
        'icon',
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
     * Scope to get only active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the role's display name with color.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    /**
     * Check if the role is active.
     */
    public function isActive()
    {
        return $this->active;
    }
}