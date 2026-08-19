<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Coupon;
use App\Models\Client;
use App\Services\LoyaltyService;
use App\Events\KdsOrderUpdated;
use App\Helpers\StoreHoursHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Carbon\Carbon;

class StripeWebController extends Controller
{
    /**
     * Create a secure Stripe Checkout Session with Promo Codes & Loyalty Points support.
     */
    public function createCheckoutSession(Request $request)
    {
        // 🚀 1. ONLINE ORDERING GUARD: Check if store is open and online orders are enabled
        if (!StoreHoursHelper::canAcceptOnlineOrders()) {
            return response()->json([
                'error'    => StoreHoursHelper::getClosedMessage(),
                'schedule' => 'Opening Hours: ' . StoreHoursHelper::getScheduleText(),
            ], 403);
        }

        $request->validate([
            'cart'             => 'required|array|min:1',
            'cart.*.id'        => 'required|exists:products,id',
            'cart.*.quantity'  => 'required|integer|min:1',
            'coupon_code'      => 'nullable|string',
            'points_to_redeem' => 'nullable|integer|min:0',
        ]);

        try {
            $secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

            if (!$secretKey) {
                return response()->json([
                    'error' => 'STRIPE_SECRET is missing in Laravel .env or config/services.php!'
                ], 500);
            }

            Stripe::setApiKey($secretKey);

            $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id ?? $request->input('client_id') ?? null;
            $couponCode = $request->input('coupon_code');
            $pointsToRedeem = (int) $request->input('points_to_redeem', 0);

            // 2. Calculate Cart Subtotal
            $cartSubtotal = 0;
            foreach ($request->cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);
                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $cartSubtotal += ($basePrice + $extraPrice) * (int) $itemData['quantity'];
            }

            // 3. Calculate Promo Code Discount
            $discountAmount = 0.00;
            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->where('is_active', true)->first();
                if ($coupon && $coupon->isValidForAmount($cartSubtotal)) {
                    $discountAmount += $coupon->calculateDiscount($cartSubtotal);
                }
            }

            // 4. Calculate Loyalty Points Discount
            if ($pointsToRedeem > 0 && $clientId) {
                $client = Client::find($clientId);
                if ($client && $client->loyalty_points >= $pointsToRedeem) {
                    $loyaltyDiscount = round($pointsToRedeem * LoyaltyService::POINT_REDEMPTION_VALUE, 2);
                    $discountAmount += $loyaltyDiscount;
                }
            }

            // Cap discount to cart subtotal
            $discountAmount = min($discountAmount, $cartSubtotal);

            // Ratio to apply discount proportionally across line items for Stripe Checkout
            $discountRatio = ($cartSubtotal > 0) ? (($cartSubtotal - $discountAmount) / $cartSubtotal) : 1;

            $lineItems = [];
            foreach ($request->cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);
                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $finalUnitPrice = ($basePrice + $extraPrice) * $discountRatio;

                $notesText = !empty($itemData['notes']) ? ' (' . implode(', ', $itemData['notes']) . ')' : '';

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->name . $notesText,
                        ],
                        'unit_amount' => (int) round($finalUnitPrice * 100),
                    ],
                    'quantity' => (int) $itemData['quantity'],
                ];
            }

            $frontendUrl = $request->header('Origin') ?? env('FRONTEND_URL', 'https://next-click-and-cloonect-pos-web.vercel.app');

            // 🚀 STRIPE METADATA FIX: Cast all values explicitly as strings
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'success_url'          => "{$frontendUrl}/client/profile?status=success&session_id={CHECKOUT_SESSION_ID}",
                'cancel_url'           => "{$frontendUrl}/order?status=cancelled",
                'metadata'             => [
                    'cart'             => json_encode($request->cart),
                    'client_id'        => (string) ($clientId ?? ''),
                    'coupon_code'      => (string) ($couponCode ?? ''),
                    'discount_amount'  => (string) $discountAmount,
                    'points_to_redeem' => (string) $pointsToRedeem,
                ]
            ]);

            return response()->json(['url' => $session->url], 200);
        } catch (\Exception $e) {
            Log::error('Stripe Session creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create payment session.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🚀 FAIL-SAFE VERIFICATION ENDPOINT
     */
    public function verifySession(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->json(['error' => 'No session_id provided'], 400);
        }

        $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET');
        Stripe::setApiKey($stripeSecret);

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $cartJson = null;
                if (isset($session->metadata->cart)) {
                    $cartJson = $session->metadata->cart;
                }
                
                $clientId = $session->metadata->client_id ?? null;

                if (!$cartJson) {
                    return response()->json(['error' => 'Cart metadata missing'], 400);
                }

                // Create Order (Deduplicated)
                $order = $this->processOrderCreation($session, json_decode($cartJson, true), $clientId);

                return response()->json([
                    'success' => true,
                    'order'   => $order,
                ], 200);
            }

            return response()->json(['success' => false, 'message' => 'Payment not paid'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe Session Verification Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Securely listen to Stripe's cloud server webhook.
     */
    public function handleWebhook(Request $request)
    {
        $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET');
        Stripe::setApiKey($stripeSecret);

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature') ?? $request->header('HTTP_STRIPE_SIGNATURE');
        $endpointSecret = config('services.stripe.webhook.secret') ?? env('STRIPE_WEBHOOK_SECRET');

        $event = null;

        try {
            if ($endpointSecret && $sigHeader) {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Invalid Payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload.'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $eventType = is_object($event) && isset($event->type) ? $event->type : null;

        if ($eventType === 'checkout.session.completed') {
            $session = is_object($event->data) ? $event->data->object : null;

            if (!$session) {
                return response()->json(['error' => 'Session object missing'], 400);
            }

            $cartJson = $session->metadata->cart ?? null;
            $clientId = $session->metadata->client_id ?? null;

            if ($cartJson) {
                $this->processOrderCreation($session, json_decode($cartJson, true), $clientId);
                return response()->json(['success' => true], 200);
            }
        }

        return response()->json(['message' => 'Event received.'], 200);
    }

    /**
     * 🚀 SHARED ORDER CREATION ENGINE (With Extras, Coupons, & Loyalty Points)
     */
    private function processOrderCreation($session, array $cart, $clientId = null)
    {
        $paymentIntent = is_object($session) ? ($session->payment_intent ?? null) : ($session['payment_intent'] ?? null);
        $paymentIntentId = is_object($paymentIntent) ? ($paymentIntent->id ?? null) : $paymentIntent;

        if (!empty($paymentIntentId)) {
            $existingOrder = Order::where('payment_intent_id', $paymentIntentId)->first();
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        // 🚀 METADATA EXTRACTION FIX: Extract array safely using toArray()
        $metadata = [];
        if (isset($session->metadata)) {
            if (is_object($session->metadata) && method_exists($session->metadata, 'toArray')) {
                $metadata = $session->metadata->toArray();
            } elseif (is_array($session->metadata)) {
                $metadata = $session->metadata;
            }
        }

        $couponCode     = !empty($metadata['coupon_code']) ? strtoupper(trim($metadata['coupon_code'])) : null;
        $discountAmount = isset($metadata['discount_amount']) ? (float) $metadata['discount_amount'] : 0.00;
        $pointsToRedeem = isset($metadata['points_to_redeem']) ? (int) $metadata['points_to_redeem'] : 0;

        $clientName = 'Web Customer';
        $clientPhone = null;

        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $clientName = $client->name;
                $clientPhone = $client->phone ?? null;
            }
        }

        DB::beginTransaction();
        try {
            $subtotalExclVat = 0;
            $vatAmount = 0;
            $totalInclVat = 0;
            $orderItems = [];

            foreach ($cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);
                $quantity = (int) $itemData['quantity'];
                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $unitPriceWithExtras = $basePrice + $extraPrice;
                $vatRate = (float) ($product->vat_rate ?? 10.0);

                $itemTotalTtc = $unitPriceWithExtras * $quantity;
                $itemSubtotalHt = $itemTotalTtc / (1 + ($vatRate / 100));
                $itemVat = $itemTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount += $itemVat;
                $totalInclVat += $itemTotalTtc;

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'notes'        => $itemData['notes'] ?? null,
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPriceWithExtras,
                    'vat_rate'     => $vatRate,
                    'subtotal'     => $itemTotalTtc,
                ];
            }

            // Apply Discount to Final Grand Totals
            $finalTotalTtc = max(0, $totalInclVat - $discountAmount);
            $finalSubtotalHt = $finalTotalTtc / (1 + (10.0 / 100));
            $finalVatAmount = $finalTotalTtc - $finalSubtotalHt;

            $lastSeqOrder = Order::orderBy('sequence_number', 'desc')->lockForUpdate()->first();
            $sequenceNumber = $lastSeqOrder ? ($lastSeqOrder->sequence_number + 1) : 1;
            $completedAt = Carbon::now();

            $lastHashOrder = Order::whereNotNull('hash')->where('hash', '!=', '')->orderBy('sequence_number', 'desc')->first();
            $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash)) ? $lastHashOrder->hash : '0000000000000000000000000000000000000000000000000000000000000000';

            $dataToHash = "{$sequenceNumber}|" . number_format($finalSubtotalHt, 2, '.', '') . "|" . number_format($finalVatAmount, 2, '.', '') . "|" . number_format($finalTotalTtc, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            $currentHash = hash('sha256', $dataToHash);

            $order = Order::create([
                'uuid'               => (string) \Illuminate\Support\Str::uuid(),
                'payment_intent_id'  => $paymentIntentId,
                'client_id'          => $clientId,
                'customer_name'      => $clientName,
                'customer_phone'     => $clientPhone,
                'order_type'         => 'click_and_collect',
                'sequence_number'    => $sequenceNumber,
                'coupon_code'        => $couponCode,
                'discount_amount'    => $discountAmount,
                'points_redeemed'    => $pointsToRedeem,
                'subtotal_excl_vat'  => round($finalSubtotalHt, 2),
                'vat_amount'         => round($finalVatAmount, 2),
                'total_incl_vat'     => round($finalTotalTtc, 2),
                'hash'               => $currentHash,
                'previous_hash'      => $previousHash,
                'completed_at'       => $completedAt,
                'preparation_status' => 'not_accepted',
                'status'             => 'completed',
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'notes'        => $item['notes'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'vat_rate'     => $item['vat_rate'],
                    'subtotal'     => $item['subtotal'],
                ]);

                $recipes = Recipe::where('product_id', $item['product_id'])->get();
                foreach ($recipes as $recipe) {
                    Ingredient::where('id', $recipe->ingredient_id)->decrement('stock_level', $item['quantity'] * $recipe->quantity);
                }
            }

            Payment::create([
                'order_id' => $order->id,
                'amount'   => round($finalTotalTtc, 2),
                'method'   => 'stripe_checkout',
            ]);

            // Increment Coupon Usage Counter
            if ($couponCode) {
                Coupon::where('code', strtoupper($couponCode))->increment('uses_count');
            }

            // Deduct Redeemed Loyalty Points
            if ($pointsToRedeem > 0 && $clientId) {
                $client = Client::find($clientId);
                if ($client) {
                    LoyaltyService::redeemPoints($client, $pointsToRedeem, $order);
                }
            }

            // Award New Loyalty Points (1 pt per €1 spent)
            if ($clientId) {
                LoyaltyService::awardPointsForOrder($order);
            }

            DB::commit();

            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (\Exception $e) {}

            return $order;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stripe Order creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}