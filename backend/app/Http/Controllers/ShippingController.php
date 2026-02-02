<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    /**
     * Shipping rates by governorate/zone
     * Based on the shipping price list
     */
    private array $shippingZones = [
        // مترو - 65 جنيه (24 ساعة)
        'metro' => [
            'price' => 65,
            'delivery_time' => '24',
            'areas' => ['مترو']
        ],

        // القاهرة والجيزة - 75 جنيه (24 ساعة)
        'cairo_giza' => [
            'price' => 75,
            'delivery_time' => '24',
            'areas' => ['القاهرة', 'الجيزة']
        ],

        // مناطق قريبة - 80 جنيه (24 ساعة)
        'nearby_areas' => [
            'price' => 80,
            'delivery_time' => '24',
            'areas' => [
                'التجمع', 'حلوان', 'حدائق الأهرام', 'الشيراتون',
                'الرحاب', 'أبو زعبل', 'كرداسة', 'أبو رواش',
                'المنصورية', 'بدرشين', 'الحوامدية', 'العياط',
                '6 أكتوبر', 'الشيخ زايد', 'المعادي', 'مدينة نصر'
            ]
        ],

        // العبور ومدينتي والشروق - 85 جنيه (24 ساعة)
        'new_cities' => [
            'price' => 85,
            'delivery_time' => '24',
            'areas' => [
                'العبور', 'مدينتي', 'الشروق', 'العاشر من رمضان',
                'بدر', 'المستقبل', 'العاصمة الإدارية'
            ]
        ],

        // الإسكندرية والقناة - 90 جنيه (24-48 ساعة)
        'alexandria_canal' => [
            'price' => 90,
            'delivery_time' => '24-48',
            'areas' => ['الإسكندرية', 'الاسكندرية', 'الإسماعيلية', 'السويس', 'بورسعيد']
        ],

        // محافظات الدلتا - 95 جنيه (24-48 ساعة)
        'delta' => [
            'price' => 95,
            'delivery_time' => '24-48',
            'areas' => [
                'الدقهلية', 'القليوبية', 'الغربية', 'الشرقية',
                'دمياط', 'كفر الشيخ', 'البحيرة', 'المنوفية'
            ]
        ],

        // صعيد مصر - 105 جنيه (24-48 ساعة)
        'upper_egypt' => [
            'price' => 105,
            'delivery_time' => '24-48',
            'areas' => [
                'الفيوم', 'بني سويف', 'المنيا', 'أسيوط',
                'سوهاج', 'قنا', 'الأقصر', 'أسوان'
            ]
        ],

        // مطروح والبحر الأحمر - 115 جنيه (24-48 ساعة)
        'matrouh_red_sea' => [
            'price' => 115,
            'delivery_time' => '24-48',
            'areas' => [
                'مرسى مطروح', 'الغردقة', 'البحر الأحمر',
                'سيدي عبدالرحمن', 'العلمين', 'الساحل الشمالي'
            ]
        ],

        // شرم الشيخ - 130 جنيه (24-48 ساعة)
        'sharm' => [
            'price' => 130,
            'delivery_time' => '24-48',
            'areas' => ['شرم الشيخ', 'رأس غارب', 'مرسى علم', 'سفاجا']
        ],

        // سيناء - 135 جنيه (24-48 ساعة)
        'sinai' => [
            'price' => 135,
            'delivery_time' => '24-48',
            'areas' => [
                'شمال سيناء', 'جنوب سيناء', 'العريش',
                'دهب', 'نويبع', 'طابا', 'الطور', 'رأس سدر'
            ]
        ]
    ];

    /**
     * Get all governorates with shipping prices
     */
    public function index(): JsonResponse
    {
        $governorates = [];

        foreach ($this->shippingZones as $zone => $data) {
            foreach ($data['areas'] as $area) {
                $governorates[] = [
                    'name' => $area,
                    'zone' => $zone,
                    'price' => $data['price'],
                    'deliveryTime' => $data['delivery_time']
                ];
            }
        }

        // Sort alphabetically by name
        usort($governorates, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return response()->json([
            'success' => true,
            'governorates' => $governorates
        ]);
    }

    /**
     * Get shipping price for a specific governorate
     */
    public function getPrice(Request $request): JsonResponse
    {
        $governorate = $request->input('governorate');

        if (!$governorate) {
            return response()->json([
                'success' => false,
                'message' => 'Governorate is required'
            ], 400);
        }

        $shippingInfo = $this->findShippingInfo($governorate);

        if (!$shippingInfo) {
            // Default price for unknown areas
            return response()->json([
                'success' => true,
                'governorate' => $governorate,
                'price' => 100,
                'deliveryTime' => '48-72',
                'message' => 'منطقة غير محددة - سعر تقريبي'
            ]);
        }

        return response()->json([
            'success' => true,
            'governorate' => $governorate,
            'price' => $shippingInfo['price'],
            'deliveryTime' => $shippingInfo['delivery_time'],
            'zone' => $shippingInfo['zone']
        ]);
    }

    /**
     * Get shipping zones summary
     */
    public function zones(): JsonResponse
    {
        $zones = [];

        foreach ($this->shippingZones as $zone => $data) {
            $zones[] = [
                'zone' => $zone,
                'price' => $data['price'],
                'deliveryTime' => $data['delivery_time'],
                'areas' => $data['areas']
            ];
        }

        return response()->json([
            'success' => true,
            'zones' => $zones
        ]);
    }

    /**
     * Find shipping info for a governorate
     */
    private function findShippingInfo(string $governorate): ?array
    {
        $normalizedInput = $this->normalizeArabic($governorate);

        foreach ($this->shippingZones as $zone => $data) {
            foreach ($data['areas'] as $area) {
                $normalizedArea = $this->normalizeArabic($area);

                // Check for exact match or partial match
                if ($normalizedArea === $normalizedInput ||
                    str_contains($normalizedInput, $normalizedArea) ||
                    str_contains($normalizedArea, $normalizedInput)) {
                    return [
                        'zone' => $zone,
                        'price' => $data['price'],
                        'delivery_time' => $data['delivery_time']
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Normalize Arabic text for comparison
     */
    private function normalizeArabic(string $text): string
    {
        // Remove common Arabic diacritics and normalize characters
        $text = mb_strtolower($text);
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}
