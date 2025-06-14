<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoogleSheet extends Model
{
    use HasFactory
    ,SoftDeletes;




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
        'ou_id'
    ];


    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
}
