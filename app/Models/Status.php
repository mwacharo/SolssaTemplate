<?php

namespace App\Models;

use App\Traits\BelongsToUserAndCountry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Status extends Model
{
    /** @use HasFactory<\Database\Factories\StatusFactory> */
    use HasFactory;
    use BelongsToUserAndCountry;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name',
        'status_category',
        'description',
        'color',
        'country_id',
    ];

    // Spatie Activity Log settings
    protected static $logName = 'status';
    protected static $logAttributes = [
        'name',
        'status_category',
        'description',
        'color',
        'country_id',
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName(self::$logName)
            ->logOnly(self::$logAttributes)
            ->logOnlyDirty(self::$logOnlyDirty)
            ->dontSubmitEmptyLogs();
    }

//   status has many status categories
    public function statusCategories()
    {
        return $this->hasMany(StatusCategory::class);
    }
}
