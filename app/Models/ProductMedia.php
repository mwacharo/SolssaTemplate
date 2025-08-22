<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    use HasFactory;

    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'media_type',     // image, video, pdf, doc
        'url',            // storage/public path or external link
        'alt_text',       // for SEO & accessibility
        'is_primary',     // true if main image/video
        'position',       // order in gallery
    ];

    /*********************************
     * Relationships
     *********************************/
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*********************************
     * Accessors / Mutators
     *********************************/
    public function getFullUrlAttribute()
    {
        // If stored locally, return storage path
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            return asset('storage/' . $this->url);
        }
        return $this->url;
    }

    /*********************************
     * Scopes
     *********************************/
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('media_type', $type);
    }
}
