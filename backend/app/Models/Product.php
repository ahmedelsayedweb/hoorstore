<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'salePrice',
        'category',
        'image',
        'images',
        'sizes',
        'description',
        'inStock',
    ];

    protected $casts = [
        'price' => 'float',
        'salePrice' => 'float',
        'images' => 'array',
        'sizes' => 'array',
        'inStock' => 'boolean',
    ];
}
