<?php

namespace App\Models;

use App\Models\Scopes\CountryScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\BelongsToUserAndCountry;
use App\Traits\BelongsToVendor; 




class GoogleSheet extends Model
{
    use HasFactory
    ,SoftDeletes;

    use BelongsToUserAndCountry;
        use BelongsToVendor;



      protected static function booted()
    {
        static::addGlobalScope(new CountryScope);
    }

    protected $fillable = [
        'sheet_name',
        'post_spreadsheet_id',
        'active',
        'auto_sync',
        'sync_all',
        'sync_interval',
        'last_order_synced',
        'last_order_upload',
        'last_product_synced',
        'is_current',
        'order_prefix',
        'vendor_id',
        'lastUpdatedOrderNumber',
        'country_id'
    ];


    public function vendor()
    {
        return $this->belongsTo(\App\Models\User::class, 'vendor_id');
    }
    
}
