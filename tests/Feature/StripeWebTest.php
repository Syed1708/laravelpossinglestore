<?php

use App\Events\KdsOrderUpdated;
use App\Models\Category;
use App\Models\Client;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([KdsOrderUpdated::class]);

    // Ensure store is open 24/7 in test environment
    $settings = StoreSetting::getSettings();
    $settings->update([
        'is_store_open'         => true,
        'online_orders_enabled' => true,
        'shift1_start'          => '00:00',
        'shift1_end'            => '23:59',
        'shift2_start'          => '00:00',
        'shift2_end'            => '23:59',
    ]);

    $this->category = Category::create([
        'name'             => 'Food',
        'show_on_chef_kds' => true,
    ]);

    // Food item taxed at 10.0%
    $this->burger = Product::create([
        'name'        => 'Gourmet Bacon Burger',
        'category_id' => $this->category->id,
        'price'       => 10.00,
        'vat_rate'    => 10.0,
        'is_active'   => true,
    ]);

    // Cold packaged drink taxed at 5.5%
    $this->drink = Product::create([
        'name'        => 'Bottled Water',
        'category_id' => $this->category->id,
        'price'       => 2.00,
        'vat_rate'    => 5.5,
        'is_active'   => true,
    ]);

    $this->client = Client::create([
        'name'           => 'Jane Doe',
        'email'          => 'jane@example.com',
        'password'       => bcrypt('password123'),
        'loyalty_points' => 100, // €5.00 value
    ]);
});

it('blocks checkout session creation when the store is marked closed', function () {
    StoreSetting::getSettings()->update(['is_store_open' => false]);

    $this->postJson('/api/v1/stripe/checkout-session', [
        'cart' => [
            ['id' => $this->burger->id, 'quantity' => 1],
        ],
    ])
    ->assertStatus(403)
    ->assertJsonStructure(['error', 'schedule']);
});

it('processes checkout.session.completed webhook with proportional vat splitting', function () {
    $paymentIntentId = 'pi_test_' . Str::random(24);

    // Mock cart: 1 Burger (€10 @ 10%) + 1 Drink (€2 @ 5.5%) = €12 Gross
    $cart = [
        ['id' => $this->burger->id, 'quantity' => 1, 'extraPrice' => 0],
        ['id' => $this->drink->id, 'quantity' => 1, 'extraPrice' => 0],
    ];

    // Payload simulating Stripe Webhook
    $webhookPayload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'payment_intent' => $paymentIntentId,
                'metadata'       => [
                    'cart'             => json_encode($cart),
                    'client_id'        => (string) $this->client->id,
                    'coupon_code'      => '',
                    'discount_amount'  => '0.00',
                    'points_to_redeem' => '0',
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/stripe/webhook', $webhookPayload);

    $response->assertOk();

    $this->assertDatabaseHas('orders', [
        'payment_intent_id' => $paymentIntentId,
        'client_id'         => $this->client->id,
        'order_type'        => 'click_and_collect',
        'total_incl_vat'    => 12.00,
    ]);

    $order = Order::where('payment_intent_id', $paymentIntentId)->first();

    // Verify order item breakdown and distinct VAT rates
    expect($order->items)->toHaveCount(2);

    $burgerLine = $order->items->firstWhere('product_id', $this->burger->id);
    $drinkLine  = $order->items->firstWhere('product_id', $this->drink->id);

    expect((float) $burgerLine->vat_rate)->toBe(10.0)
        ->and((float) $drinkLine->vat_rate)->toBe(5.5)
        ->and($order->hash)->not->toBeEmpty();

    // Customer earns 1 point per €1 spent (12 points)
    expect($this->client->fresh()->loyalty_points)->toBe(112);

    Event::assertDispatched(KdsOrderUpdated::class);
});

it('prevents duplicate order creation when stripe webhook is delivered twice', function () {
    $paymentIntentId = 'pi_test_idempotent_' . Str::random(20);

    $cart = [
        ['id' => $this->burger->id, 'quantity' => 1, 'extraPrice' => 0],
    ];

    $webhookPayload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'payment_intent' => $paymentIntentId,
                'metadata'       => [
                    'cart'             => json_encode($cart),
                    'client_id'        => (string) $this->client->id,
                    'coupon_code'      => '',
                    'discount_amount'  => '0.00',
                    'points_to_redeem' => '0',
                ],
            ],
        ],
    ];

    // Delivery 1
    $this->postJson('/api/v1/stripe/webhook', $webhookPayload)->assertOk();
    expect(Order::where('payment_intent_id', $paymentIntentId)->count())->toBe(1);

    // Delivery 2 (Duplicate)
    $this->postJson('/api/v1/stripe/webhook', $webhookPayload)->assertOk();
    expect(Order::where('payment_intent_id', $paymentIntentId)->count())->toBe(1);
});

it('properly increments coupon usage and deducts loyalty points upon webhook confirmation', function () {
    $coupon = Coupon::create([
        'code'             => 'PROMO10',
        'type'             => 'fixed',
        'value'            => 2.00,
        'min_order_amount' => 5.00,
        'uses_count'       => 0,
        'is_active'        => true,
    ]);

    $paymentIntentId = 'pi_test_promo_' . Str::random(20);

    $cart = [
        ['id' => $this->burger->id, 'quantity' => 1, 'extraPrice' => 0],
    ];

    $webhookPayload = [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'payment_intent' => $paymentIntentId,
                'metadata'       => [
                    'cart'             => json_encode($cart),
                    'client_id'        => (string) $this->client->id,
                    'coupon_code'      => 'PROMO10',
                    'discount_amount'  => '2.00',
                    'points_to_redeem' => '40', // 40 pts = €2.00
                ],
            ],
        ],
    ];

    $this->postJson('/api/v1/stripe/webhook', $webhookPayload)->assertOk();

    expect($coupon->fresh()->uses_count)->toBe(1);

    // Client had 100 points, redeemed 40 (-40 = 60), earned €8.00 order rounded (+8 = 68)
    expect($this->client->fresh()->loyalty_points)->toBe(68);
});