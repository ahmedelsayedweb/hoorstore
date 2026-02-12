<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'salePrice',
        'category',
        'image',
        'images',
        'sizes',
        'colors',
        'heights',
        'weights',
        'variants',
        'description',
        'inStock',
    ];

    protected $casts = [
        'price' => 'float',
        'salePrice' => 'float',
        'category' => 'array',
        'images' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'heights' => 'array',
        'weights' => 'array',
        'variants' => 'array',
        'inStock' => 'boolean',
    ];
}
