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
        'description',
        'inStock',
    ];

    protected $casts = [
        'price' => 'float',
        'salePrice' => 'float',
        'images' => 'array',
        'sizes' => 'array',
        'colors' => 'array',
        'heights' => 'array',
        'weights' => 'array',
        'inStock' => 'boolean',
    ];
}
