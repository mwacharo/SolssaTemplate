<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shopify extends Model
{
    /** @use HasFactory<\Database\Factories\ShopifyFactory> */
    use HasFactory;
    Use HasFactory;




    protected $fillable = [
        'shopify_key',
        'shopify_secret',
        'shopify_url',
        'shopify_name',
        'active',
        'new_api',
        'auto_sync',
        'order_webhook',
        'webhook_id',
        'product_webhook',
        'sync_interval',
        'last_order_synced',
        'last_product_synced',
        'order_prefix',
        'sync_option',
        'webhook_active',
        'vendor_id',
        'country_id'
    ];
}
