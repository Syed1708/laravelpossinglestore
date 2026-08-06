<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
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
     * Create a secure Stripe Checkout Session based on the customer's cart.
     */
    public function createCheckoutSession(Request $request)
    {
        // 🚀 HARD BACKEND CHECK: Block checkout if restaurant is closed!
        if (!StoreHoursHelper::isOpen()) {
            return response()->json([
                'error' => 'Restaurant is currently closed for online ordering.',
                'schedule' => 'Opening Hours: ' . StoreHoursHelper::getScheduleText(),
            ], 403);
        }

        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

            if (!$secretKey) {
                return response()->json([
                    'error' => 'STRIPE_SECRET is missing in Laravel .env or config/services.php!'
                ], 500);
            }

            Stripe::setApiKey($secretKey);

            $lineItems = [];


            foreach ($request->cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);

                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $finalUnitPrice = $basePrice + $extraPrice; // 🚀 Base price + paid extras!

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->name . (!empty($itemData['notes']) ? ' (' . implode(', ', $itemData['notes']) . ')' : ''),
                        ],
                        'unit_amount' => (int) round($finalUnitPrice * 100),
                    ],
                    'quantity' => $itemData['quantity'],
                ];
            }

            $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id ?? $request->input('client_id') ?? null;
            $frontendUrl = $request->header('Origin') ?? env('FRONTEND_URL', 'https://next-click-and-cloonect-pos-web.vercel.app');

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => "{$frontendUrl}/client/profile?status=success&session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => "{$frontendUrl}/order?status=cancelled",
                'metadata' => [
                    'cart' => json_encode($request->cart),
                    'client_id' => $clientId,
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
     * 🚀 FAIL-SAFE VERIFICATION ENDPOINT:
     * Called by Next.js when customer arrives on /profile?status=success&session_id=...
     * Creates order immediately if Webhook was delayed or blocked!
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
                $metadata = is_object($session->metadata) ? $session->metadata->toArray() : (array) $session->metadata;
                $cartJson = $metadata['cart'] ?? null;
                $clientId = $metadata['client_id'] ?? null;

                if (!$cartJson) {
                    return response()->json(['error' => 'Cart metadata missing'], 400);
                }

                // Create Order (Deduplicated)
                $order = $this->processOrderCreation($session, json_decode($cartJson, true), $clientId);

                return response()->json([
                    'success' => true,
                    'order' => $order,
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

            $metadata = is_object($session->metadata) ? $session->metadata->toArray() : (array) ($session->metadata ?? []);
            $cartJson = $metadata['cart'] ?? null;
            $clientId = $metadata['client_id'] ?? null;

            if ($cartJson) {
                $this->processOrderCreation($session, json_decode($cartJson, true), $clientId);
                return response()->json(['success' => true], 200);
            }
        }

        return response()->json(['message' => 'Event received.'], 200);
    }

    /**
     * 🚀 SHARED ORDER CREATION ENGINE (Deduplicated & Failsafe)
     */
    /**
     * 🚀 SHARED ORDER CREATION ENGINE (With Extra Price & Notes Support)
     */
    private function processOrderCreation($session, array $cart, $clientId = null)
    {
        // 1. Extract Payment Intent ID
        $paymentIntent = is_object($session) ? ($session->payment_intent ?? null) : ($session['payment_intent'] ?? null);
        $paymentIntentId = is_object($paymentIntent) ? ($paymentIntent->id ?? null) : $paymentIntent;

        // 2. Deduplication Check
        if (!empty($paymentIntentId)) {
            $existingOrder = Order::where('payment_intent_id', $paymentIntentId)->first();
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        // 3. Fetch Customer Details
        $clientName = 'Web Customer';
        $clientPhone = null;

        if ($clientId) {
            $client = \App\Models\Client::find($clientId);
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

            // 🚀 4. CALCULATE LINE ITEMS WITH EXTRAS
            foreach ($cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);
                $quantity = (int) $itemData['quantity'];

                // 🚀 STEP A: Calculate Base Price + Extra Price from Item Modifier Modal
                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $unitPriceWithExtras = $basePrice + $extraPrice; // e.g. €10.00 + €1.50 = €11.50

                $vatRate = (float) ($product->vat_rate ?? 10.0);

                // 🚀 STEP B: Calculate HT & TVA using unit price with extras
                $itemTotalTtc = $unitPriceWithExtras * $quantity;
                $itemSubtotalHt = $itemTotalTtc / (1 + ($vatRate / 100));
                $itemVat = $itemTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount += $itemVat;
                $totalInclVat += $itemTotalTtc;

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'notes'        => $itemData['notes'] ?? null,  // 🚀 Kitchen instructions array
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPriceWithExtras,        // 🚀 Saved with extra price!
                    'vat_rate'     => $vatRate,
                    'subtotal'     => $itemTotalTtc,
                ];
            }

            // 5. Sequence Number & NF525 Cryptographic Hash Chain
            $lastSeqOrder = Order::orderBy('sequence_number', 'desc')->lockForUpdate()->first();
            $sequenceNumber = $lastSeqOrder ? ($lastSeqOrder->sequence_number + 1) : 1;
            $completedAt = Carbon::now();

            $lastHashOrder = Order::whereNotNull('hash')->where('hash', '!=', '')->orderBy('sequence_number', 'desc')->first();
            $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash))
                ? $lastHashOrder->hash
                : '0000000000000000000000000000000000000000000000000000000000000000';

            // 🚀 STEP C: Hash computed on grand totals INCLUDING EXTRAS
            $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            $currentHash = hash('sha256', $dataToHash);

            // 🚀 STEP D: Save Order Record with accurate grand totals
            $order = Order::create([
                'uuid'               => (string) \Illuminate\Support\Str::uuid(),
                'payment_intent_id'  => $paymentIntentId,
                'client_id'          => $clientId,
                'customer_name'      => $clientName,
                'customer_phone'     => $clientPhone,
                'order_type'         => 'click_and_collect',
                'sequence_number'    => $sequenceNumber,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2), // 🚀 HT (incl. extras)
                'vat_amount'         => round($vatAmount, 2),        // 🚀 TVA (incl. extras)
                'total_incl_vat'     => round($totalInclVat, 2),     // 🚀 TTC (incl. extras)
                'hash'               => $currentHash,
                'previous_hash'      => $previousHash,
                'completed_at'       => $completedAt,
                'preparation_status' => 'not_accepted',
                'status'             => 'completed',
            ]);

            // 🚀 STEP E: Save Order Items with clean notes & updated unit_price
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'notes'        => $item['notes'],      // 🚀 ["No Onions", "Extra Bacon"]
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'], // 🚀 €11.50
                    'vat_rate'     => $item['vat_rate'],
                    'subtotal'     => $item['subtotal'],
                ]);

                // Deduct raw ingredients from inventory
                $recipes = Recipe::where('product_id', $item['product_id'])->get();
                foreach ($recipes as $recipe) {
                    Ingredient::where('id', $recipe->ingredient_id)->decrement('stock_level', $item['quantity'] * $recipe->quantity);
                }
            }

            // 🚀 STEP F: Save Payment Log matching exact Stripe total
            Payment::create([
                'order_id' => $order->id,
                'amount'   => round($totalInclVat, 2), // 🚀 Matches Stripe Payment
                'method'   => 'stripe_checkout',
            ]);

            DB::commit();

            // Broadcast to KDS & Admin Online Orders Page
            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (\Exception $e) {
                Log::warning('WebSocket event failed during Stripe order creation: ' . $e->getMessage());
            }

            return $order;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stripe Order creation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
