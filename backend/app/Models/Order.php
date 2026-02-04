<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'contact',
        'country',
        'full_name',
        'address_details',
        'governorate',
        'phone',
        'phone2',
        'notes',
        'shipping_method',
        'payment_method',
        'billing_address',
        'coupon_code',
        'discount_amount',
        'subtotal',
        'shipping',
        'total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'shipping' => 'float',
        'total' => 'float',
        'discount_amount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getDeliveryAttribute()
    {
        return [
            'country' => $this->country,
            'fullName' => $this->full_name,
            'addressDetails' => $this->address_details,
            'governorate' => $this->governorate,
            'phone' => $this->phone,
            'phone2' => $this->phone2,
        ];
    }
}
