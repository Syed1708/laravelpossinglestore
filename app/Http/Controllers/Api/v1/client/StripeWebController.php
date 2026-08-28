<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Client;
use App\Services\Fiscal\FiscalLedgerService;
use App\Services\Inventory\StockService;
use App\Services\Orders\SequenceService;
use App\Services\LoyaltyService;
use App\Events\KdsOrderUpdated;
use App\Helpers\StoreHoursHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Throwable;

class StripeWebController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected FiscalLedgerService $fiscalService,
        protected SequenceService $sequenceService
    ) {}

    /**
     * Create a secure Stripe Checkout Session with Promo Codes & Loyalty Points support.
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
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
                return response()->json(['error' => 'Stripe secret key missing.'], 500);
            }

            Stripe::setApiKey($secretKey);

            $clientId       = auth('sanctum')->id() ?? $request->user('sanctum')?->id ?? $request->input('client_id') ?? null;
            $couponCode     = $request->input('coupon_code');
            $pointsToRedeem = (int) $request->input('points_to_redeem', 0);

            // 1. Calculate Cart Subtotal
            $cartSubtotal = 0;
            foreach ($request->cart as $itemData) {
                $product       = Product::findOrFail($itemData['id']);
                $basePrice     = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice    = (float) ($itemData['extraPrice'] ?? 0);
                $cartSubtotal += ($basePrice + $extraPrice) * (int) $itemData['quantity'];
            }

            // 2. Promo Code & Loyalty Points Discount Calculation
            $discountAmount = 0.00;
            if ($couponCode) {
                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->where('is_active', true)->first();
                if ($coupon && $coupon->isValidForAmount($cartSubtotal)) {
                    $discountAmount += $coupon->calculateDiscount($cartSubtotal);
                }
            }

            if ($pointsToRedeem > 0 && $clientId) {
                $client = Client::find($clientId);
                if ($client && $client->loyalty_points >= $pointsToRedeem) {
                    $discountAmount += round($pointsToRedeem * LoyaltyService::POINT_REDEMPTION_VALUE, 2);
                }
            }

            $discountAmount = min($discountAmount, $cartSubtotal);
            $discountRatio  = ($cartSubtotal > 0) ? (($cartSubtotal - $discountAmount) / $cartSubtotal) : 1;

            $lineItems = [];
            foreach ($request->cart as $itemData) {
                $product         = Product::findOrFail($itemData['id']);
                $basePrice       = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice      = (float) ($itemData['extraPrice'] ?? 0);
                $finalUnitPrice  = ($basePrice + $extraPrice) * $discountRatio;
                $notesText       = !empty($itemData['notes']) ? ' (' . implode(', ', $itemData['notes']) . ')' : '';

                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => [
                            'name' => $product->name . $notesText,
                        ],
                        'unit_amount' => (int) round($finalUnitPrice * 100),
                    ],
                    'quantity' => (int) $itemData['quantity'],
                ];
            }

            $frontendUrl = $request->header('Origin') ?? env('FRONTEND_URL', 'https://next-click-and-cloonect-pos-web.vercel.app');

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
        } catch (Throwable $e) {
            Log::error('Stripe Session creation failed: ' . $e->getMessage());
            return response()->json([
                'error'   => 'Failed to create payment session.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fail-safe verification endpoint
     */
    public function verifySession(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->json(['error' => 'No session_id provided'], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $cartJson = $session->metadata->cart ?? null;
                $clientId = $session->metadata->client_id ?? null;

                if (!$cartJson) {
                    return response()->json(['error' => 'Cart metadata missing'], 400);
                }

                $order = $this->processOrderCreation($session, json_decode($cartJson, true), $clientId);

                return response()->json([
                    'success' => true,
                    'order'   => $order,
                ], 200);
            }

            return response()->json(['success' => false, 'message' => 'Payment not completed'], 400);
        } catch (Throwable $e) {
            Log::error('Stripe Session Verification Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook Handler
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature') ?? $request->header('HTTP_STRIPE_SIGNATURE');
        $endpointSecret = config('services.stripe.webhook.secret') ?? env('STRIPE_WEBHOOK_SECRET');

        try {
            if ($endpointSecret && $sigHeader) {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (Throwable $e) {
            Log::error('Stripe Webhook Signature/Payload Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $eventType = is_object($event) && isset($event->type) ? $event->type : null;

        if ($eventType === 'checkout.session.completed') {
            $session  = is_object($event->data) ? $event->data->object : null;
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
     * Shared Atomic Order Creation Engine (Dynamic TVA 5.5% / 10% / 20% & Single Stock Deductions)
     */
    private function processOrderCreation($session, array $cart, $clientId = null): Order
    {
        $paymentIntent   = is_object($session) ? ($session->payment_intent ?? null) : ($session['payment_intent'] ?? null);
        $paymentIntentId = is_object($paymentIntent) ? ($paymentIntent->id ?? null) : $paymentIntent;

        if (!empty($paymentIntentId)) {
            $existingOrder = Order::where('payment_intent_id', $paymentIntentId)->first();
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        $metadata = [];
        if (isset($session->metadata)) {
            $metadata = is_object($session->metadata) && method_exists($session->metadata, 'toArray')
                ? $session->metadata->toArray()
                : (array) $session->metadata;
        }

        $couponCode     = !empty($metadata['coupon_code']) ? strtoupper(trim($metadata['coupon_code'])) : null;
        $discountAmount = isset($metadata['discount_amount']) ? (float) $metadata['discount_amount'] : 0.00;
        $pointsToRedeem = isset($metadata['points_to_redeem']) ? (int) $metadata['points_to_redeem'] : 0;

        $clientName  = 'Web Customer';
        $clientPhone = null;

        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $clientName  = $client->name;
                $clientPhone = $client->phone ?? null;
            }
        }

        DB::beginTransaction();
        try {
            $grossSubtotal = 0;
            $lineItemData  = [];

            foreach ($cart as $itemData) {
                $product             = Product::findOrFail($itemData['id']);
                $quantity            = (int) $itemData['quantity'];
                $basePrice           = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice          = (float) ($itemData['extraPrice'] ?? 0);
                $unitPriceWithExtras = $basePrice + $extraPrice;
                $vatRate             = (float) ($product->vat_rate ?? 10.0);
                $itemTotalTtc        = $unitPriceWithExtras * $quantity;

                $grossSubtotal += $itemTotalTtc;
                $lineItemData[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'notes'        => $itemData['notes'] ?? null,
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPriceWithExtras,
                    'vat_rate'     => $vatRate,
                    'raw_total'    => $itemTotalTtc,
                ];
            }

            // 🚀 DYNAMIC TVA SPLIT: Proportionally apply discount across items to maintain exact per-product VAT rates
            $discountRatio = ($grossSubtotal > 0) ? max(0, ($grossSubtotal - $discountAmount) / $grossSubtotal) : 1;

            $subtotalExclVat = 0;
            $vatAmount       = 0;
            $totalInclVat    = 0;
            $finalItems      = [];
            $stockItems      = [];

            foreach ($lineItemData as $line) {
                $discountedTotalTtc = round($line['raw_total'] * $discountRatio, 2);
                $discountedUnitTtc  = $line['quantity'] > 0 ? round($discountedTotalTtc / $line['quantity'], 2) : 0;
                $itemSubtotalHt     = $discountedTotalTtc / (1 + ($line['vat_rate'] / 100));
                $itemVat            = $discountedTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount       += $itemVat;
                $totalInclVat    += $discountedTotalTtc;

                $finalItems[] = [
                    'product_id'   => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'notes'        => $line['notes'],
                    'quantity'     => $line['quantity'],
                    'unit_price'   => $discountedUnitTtc,
                    'vat_rate'     => $line['vat_rate'],
                    'subtotal'     => $discountedTotalTtc,
                ];

                $stockItems[] = [
                    'product_id' => $line['product_id'],
                    'quantity'   => $line['quantity'],
                ];
            }

            // 🚀 1. ATOMIC SEQUENCE & NF525 HASH GENERATION
            $sequenceNumber = $this->sequenceService->getNextSequenceNumber();
            $signature      = $this->fiscalService->generateSignature(
                $sequenceNumber,
                $subtotalExclVat,
                $vatAmount,
                $totalInclVat
            );

            // 🚀 2. CREATE ORDER RECORD
            $order = Order::create([
                'uuid'               => (string) Str::uuid(),
                'payment_intent_id'  => $paymentIntentId,
                'client_id'          => $clientId,
                'customer_name'      => $clientName,
                'customer_phone'     => $clientPhone,
                'order_type'         => 'click_and_collect',
                'sequence_number'    => $sequenceNumber,
                'coupon_code'        => $couponCode,
                'discount_amount'    => $discountAmount,
                'points_redeemed'    => $pointsToRedeem,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                'vat_amount'         => round($vatAmount, 2),
                'total_incl_vat'     => round($totalInclVat, 2),
                'hash'               => $signature['hash'],
                'previous_hash'      => $signature['previous_hash'],
                'completed_at'       => $signature['completed_at'],
                'preparation_status' => 'not_accepted',
                'status'             => 'completed',
            ]);

            foreach ($finalItems as $item) {
                $order->items()->create($item);
            }

            // 🚀 3. SINGLE STOCK DEDUCTION VIA STOCK SERVICE
            $this->stockService->decrementStockForItems($stockItems);

            Payment::create([
                'order_id' => $order->id,
                'amount'   => round($totalInclVat, 2),
                'method'   => 'stripe_checkout',
            ]);

            if ($couponCode) {
                Coupon::where('code', strtoupper($couponCode))->increment('uses_count');
            }

            if ($pointsToRedeem > 0 && $clientId) {
                $client = Client::find($clientId);
                if ($client) {
                    LoyaltyService::redeemPoints($client, $pointsToRedeem, $order);
                }
            }

            if ($clientId) {
                LoyaltyService::awardPointsForOrder($order);
            }

            DB::commit();

            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (Throwable $e) {}

            return $order;
        } catch (Throwable $e) {
            DB::rollback();
            Log::error('Stripe Order creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}