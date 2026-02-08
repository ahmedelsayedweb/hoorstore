<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    /**
     * Validate and get coupon details
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $this->validate($request, [
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'browser_id' => 'nullable|string',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $subtotal = $request->input('subtotal');
        $browserId = $request->input('browser_id');

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'كوبون غير صحيح',
            ], 404);
        }

        $validation = $coupon->isValid($subtotal, $browserId);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 400);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'success' => true,
            'message' => 'تم تطبيق الكوبون بنجاح',
            'coupon' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'discount' => $discount,
            ],
        ]);
    }

    /**
     * Get all coupons (admin)
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();
        return response()->json($coupons);
    }

    /**
     * Create new coupon (admin)
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'one_per_device' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $coupon = Coupon::create([
            'code' => strtoupper(trim($request->input('code'))),
            'type' => $request->input('type'),
            'value' => $request->input('value'),
            'min_order' => $request->input('min_order'),
            'max_discount' => $request->input('max_discount'),
            'usage_limit' => $request->input('usage_limit'),
            'one_per_device' => $request->input('one_per_device', false),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'coupon' => $coupon,
        ], 201);
    }

    /**
     * Update coupon (admin)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['error' => 'Coupon not found'], 404);
        }

        $this->validate($request, [
            'code' => 'sometimes|string|unique:coupons,code,' . $id,
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'one_per_device' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        if ($request->has('code')) {
            $coupon->code = strtoupper(trim($request->input('code')));
        }

        $coupon->update($request->except('code'));

        return response()->json([
            'success' => true,
            'coupon' => $coupon,
        ]);
    }

    /**
     * Delete coupon (admin)
     */
    public function destroy($id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json(['error' => 'Coupon not found'], 404);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted',
        ]);
    }
}
