<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Events\KdsOrderUpdated; // Import WebSocket Event
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
     * Create a secure Stripe Checkout Session based on the customer's cart [2].
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            // 1. Fetch Stripe Key Safely
            $secretKey = config('services.stripe.secret') ?? env('STRIPE_SECRET');

            if (!$secretKey) {
                return response()->json([
                    'error' => 'STRIPE_SECRET is missing in Laravel .env or config/services.php!'
                ], 500);
            }

            Stripe::setApiKey($secretKey);

            $lineItems = [];

            // 2. Product calculation inside try block
            foreach ($request->cart as $itemData) {
                $product = Product::findOrFail($itemData['id']);

                // Get valid price (handles fallback if field is unit_price or price)
                $price = $product->price ?? $product->unit_price ?? 0;

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product->name,
                        ],
                        'unit_amount' => (int) round($price * 100), // convert to cents
                    ],
                    'quantity' => $itemData['quantity'],
                ];
            }

            // 🚀 CAPTURE CLIENT ID FROM SANCTUM TOKEN OR REQUEST
            $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id ?? $request->input('client_id') ?? null;

            // 3. Create Stripe Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://localhost:3000/profile?status=success',
                'cancel_url' => 'http://localhost:3000/order',
                'metadata' => [
                    'cart' => json_encode($request->cart),
                    'client_id' => $clientId,
                ]
            ]);

            return response()->json(['url' => $session->url], 200);
        } catch (\Exception $e) {
            Log::error('Stripe Session creation failed: ' . $e->getMessage());

            // Returns exact error to browser console during development
            return response()->json([
                'error' => 'Failed to create payment session.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Securely listen to Stripe's cloud server webhook.
     */
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

        $payload = $request->getContent();
        $sigHeader = $request->header('HTTP_STRIPE_SIGNATURE');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        $event = null;

        try {
            if ($endpointSecret && $sigHeader) {
                $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload.'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // Extract saved metadata
            $cart = json_decode($session->metadata->cart, true);
            $clientId = $session->metadata->client_id ?? null;

            DB::beginTransaction();
            try {
                $subtotalExclVat = 0;
                $vatAmount = 0;
                $totalInclVat = 0;
                $orderItems = [];

                foreach ($cart as $itemData) {
                    $product = Product::findOrFail($itemData['id']);
                    $quantity = $itemData['quantity'];

                    $itemTotalTtc = $product->price * $quantity;
                    $itemSubtotalHt = $itemTotalTtc / (1 + ($product->vat_rate / 100));
                    $itemVat = $itemTotalTtc - $itemSubtotalHt;

                    $subtotalExclVat += $itemSubtotalHt;
                    $vatAmount += $itemVat;
                    $totalInclVat += $itemTotalTtc;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'vat_rate' => $product->vat_rate,
                        'subtotal' => $itemTotalTtc,
                    ];
                }

                $result = DB::table('orders')->selectRaw('COUNT(*) as count')->first();
                $sequenceNumber = ($result->count ?? 0) + 1;
                $completedAt = Carbon::now();

                $lastOrder = Order::orderBy('sequence_number', 'desc')->first();
                $previousHash = $lastOrder ? $lastOrder->hash : '0000000000000000000000000000000000000000000000000000000000000000';

                $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
                $currentHash = hash('sha256', $dataToHash);

                // 1. Create the Order (Linked to client_id if available!)
                $order = Order::create([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'user_id' => null, // Cashier ID null for online orders
                    'client_id' => $clientId, // 🚀 LINKED TO CLIENT!
                    'sequence_number' => $sequenceNumber,
                    'subtotal_excl_vat' => $subtotalExclVat,
                    'vat_amount' => $vatAmount,
                    'total_incl_vat' => $totalInclVat,
                    'hash' => $currentHash,
                    'previous_hash' => $previousHash,
                    'completed_at' => $completedAt,
                    'preparation_status' => 'pending' // Enters kitchen queue
                ]);

                // 2. Create Order Items & Deduct Stocks
                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'vat_rate' => $item['vat_rate'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $recipes = Recipe::where('product_id', $item['product_id'])->get();
                    foreach ($recipes as $recipe) {
                        Ingredient::where('id', $recipe->ingredient_id)->decrement('stock_level', $item['quantity'] * $recipe->quantity);
                    }
                }

                // 3. Create Payment Log
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $totalInclVat,
                    'method' => 'stripe_checkout',
                ]);

                DB::commit();

                // 🚀 Fire event with $order so Chef, Packer, and Client receive the new order live!
                event(new KdsOrderUpdated('new_orders_synced', $order));

                return response()->json(['success' => true, 'message' => 'Online order processed successfully!'], 200);
            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Stripe webhook checkout processing failed: ' . $e->getMessage());
                return response()->json(['error' => 'Database transaction failed.'], 500);
            }
        }

        return response()->json(['message' => 'Event received but ignored.'], 200);
    }
}
