<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    /**
     * Validate a promo code against cart subtotal & customer history
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0.01',
        ]);

        $code = strtoupper(trim($request->code));
        $subtotal = (float) $request->subtotal;
        $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id;

        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();

        if (!$coupon) {
            return response()->json([
                'valid'   => false,
                'message' => 'Invalid promo code.',
            ], 422);
        }

        // 🚀 1. CHECK IF LOGGED-IN CUSTOMER ALREADY USED THIS PROMO CODE
        if ($clientId) {
            $alreadyUsed = Order::where('client_id', $clientId)
                ->where('coupon_code', $code)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->exists();

            if ($alreadyUsed) {
                return response()->json([
                    'valid'   => false,
                    'message' => 'You have already used this promo code on a previous order.',
                ], 422);
            }
        }

        // 🚀 2. CHECK MIN ORDER AMOUNT & EXPIRATION / MAX USES
        if (!$coupon->isValidForAmount($subtotal)) {
            if ($subtotal < $coupon->min_order_amount) {
                return response()->json([
                    'valid'   => false,
                    'message' => "This coupon requires a minimum order amount of €" . number_format($coupon->min_order_amount, 2),
                ], 422);
            }

            return response()->json([
                'valid'   => false,
                'message' => 'This promo code has expired or reached its total usage limit.',
            ], 422);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'valid'           => true,
            'code'            => $coupon->code,
            'type'            => $coupon->type,
            'value'           => $coupon->value,
            'discount_amount' => $discount,
            'message'         => 'Promo code applied successfully!',
        ]);
    }
}