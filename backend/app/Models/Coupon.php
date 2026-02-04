<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order',
        'max_discount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'min_order' => 'float',
        'max_discount' => 'float',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Check if coupon is valid
     */
    public function isValid($orderTotal = 0)
    {
        // Check if active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'الكوبون غير فعال'];
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'تم استنفاد الكوبون'];
        }

        // Check date range
        $today = Carbon::now()->startOfDay();
        if ($this->start_date && $today->lt($this->start_date)) {
            return ['valid' => false, 'message' => 'الكوبون لم يبدأ بعد'];
        }
        if ($this->end_date && $today->gt($this->end_date)) {
            return ['valid' => false, 'message' => 'الكوبون منتهي الصلاحية'];
        }

        // Check minimum order
        if ($this->min_order && $orderTotal < $this->min_order) {
            return ['valid' => false, 'message' => "الحد الأدنى للطلب {$this->min_order} ج.م"];
        }

        return ['valid' => true, 'message' => 'كوبون صالح'];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($orderTotal)
    {
        if ($this->type === 'percentage') {
            $discount = ($orderTotal * $this->value) / 100;
            // Apply max discount if set
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } else {
            $discount = $this->value;
        }

        // Don't exceed order total
        return min($discount, $orderTotal);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->used_count++;
        $this->save();
    }
}
