<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
