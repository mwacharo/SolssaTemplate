<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    // Table associated with the model
    protected $table = 'product_images';

    // Fillable fields
    protected $fillable = [
        'product_id',
        'image_path',
        'is_featured',
    ];  
}
