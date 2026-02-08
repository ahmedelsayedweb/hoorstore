<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'browser_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
