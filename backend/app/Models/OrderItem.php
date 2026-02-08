<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'code',
        'name',
        'price',
        'image',
        'color',
        'sizes',
        'height',
        'quantity',
        'added_at',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'added_at' => 'datetime',
        'sizes' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
