<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * Governorate key to Arabic name mapping
     */
    public const GOVERNORATES_AR = [
        'cairo' => 'القاهرة',
        'giza' => 'الجيزة',
        'alexandria' => 'الإسكندرية',
        'dakahlia' => 'الدقهلية',
        'sharqia' => 'الشرقية',
        'qalyubia' => 'القليوبية',
        'beheira' => 'البحيرة',
        'gharbia' => 'الغربية',
        'menoufia' => 'المنوفية',
        'kafrElSheikh' => 'كفر الشيخ',
        'damietta' => 'دمياط',
        'portSaid' => 'بورسعيد',
        'ismailia' => 'الإسماعيلية',
        'suez' => 'السويس',
        'fayoum' => 'الفيوم',
        'beniSuef' => 'بني سويف',
        'minya' => 'المنيا',
        'assiut' => 'أسيوط',
        'sohag' => 'سوهاج',
        'qena' => 'قنا',
        'luxor' => 'الأقصر',
        'aswan' => 'أسوان',
        'redSea' => 'البحر الأحمر',
        'newValley' => 'الوادي الجديد',
        'matrouh' => 'مطروح',
        'northSinai' => 'شمال سيناء',
        'southSinai' => 'جنوب سيناء',
    ];

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

    /**
     * Get governorate name in Arabic
     */
    public function getGovernorateArabicAttribute(): string
    {
        return self::GOVERNORATES_AR[$this->governorate] ?? $this->governorate;
    }
}
