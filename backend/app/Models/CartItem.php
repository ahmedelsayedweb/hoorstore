<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'name',
        'price',
        'image',
        'color',
        'size',
        'height',
        'quantity',
        'added_at',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'added_at' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
