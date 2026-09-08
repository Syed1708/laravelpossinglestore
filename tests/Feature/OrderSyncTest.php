<?php

use App\Events\KdsOrderUpdated;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake([KdsOrderUpdated::class]);

    // Create staff user
    $this->staff = User::factory()->create();

    // Create category & product
    $this->category = Category::create([
        'name'             => 'Burgers',
        'show_on_chef_kds' => true,
    ]);

    $this->product = Product::create([
        'name'        => 'Classic Cheeseburger',
        'category_id' => $this->category->id,
        'price'       => 11.00,
        'vat_rate'    => 10.0,
        'is_active'   => true,
    ]);

    // Create raw ingredient with stock level
    $this->beefPatty = Ingredient::create([
        'name'        => 'Beef Patty',
        'stock_level' => 50,
        'alert_level' => 10,
        'unit'        => 'unit',
    ]);

    // Attach 1 patty per burger
    Recipe::create([
        'product_id'    => $this->product->id,
        'ingredient_id' => $this->beefPatty->id,
        'quantity'      => 1,
    ]);
});

it('synchronizes offline pos orders and creates gapless nf525 chained records', function () {
    $uuid = (string) Str::uuid();

    $payload = [
        'orders' => [
            [
                'uuid'              => $uuid,
                'subtotal_excl_vat' => 20.00,
                'vat_amount'        => 2.00,
                'total_incl_vat'    => 22.00,
                'completed_at'      => now()->toIso8601String(),
                'order_type'        => 'dine_in',
                'customer_name'     => 'Table 4',
                'items'             => [
                    [
                        'product_id'   => $this->product->id,
                        'product_name' => $this->product->name,
                        'quantity'     => 2,
                        'unit_price'   => 11.00,
                        'vat_rate'     => 10.0,
                        'subtotal'     => 22.00,
                        'notes'        => ['No onions'],
                    ],
                ],
                'payments' => [
                    [
                        'amount' => 22.00,
                        'method' => 'card',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', $payload);

    $response->assertOk()
        ->assertJson([
            'success'      => true,
            'synced_uuids' => [$uuid],
        ]);

    $this->assertDatabaseHas('orders', [
        'uuid'               => $uuid,
        'sequence_number'    => 1,
        'total_incl_vat'     => 22.00,
        'preparation_status' => 'accepted',
        'status'             => 'completed',
    ]);

    $order = Order::where('uuid', $uuid)->first();

    expect($order->hash)->not->toBeEmpty()
        ->and($order->previous_hash)->toBe('0000000000000000000000000000000000000000000000000000000000000000');

    // 2 burgers = 2 beef patties decremented (50 - 2 = 48)
    expect((float) $this->beefPatty->fresh()->stock_level)->toEqual(48.0);

    Event::assertDispatched(KdsOrderUpdated::class);
});

it('guarantees idempotency when the same offline uuid is synced multiple times', function () {
    $uuid = (string) Str::uuid();

    $orderData = [
        'uuid'              => $uuid,
        'subtotal_excl_vat' => 10.00,
        'vat_amount'        => 1.00,
        'total_incl_vat'    => 11.00,
        'completed_at'      => now()->toIso8601String(),
        'items'             => [
            [
                'product_id'   => $this->product->id,
                'product_name' => $this->product->name,
                'quantity'     => 1,
                'unit_price'   => 11.00,
                'vat_rate'     => 10.0,
                'subtotal'     => 11.00,
            ],
        ],
        'payments' => [
            [
                'amount' => 11.00,
                'method' => 'cash',
            ],
        ],
    ];

    // First Sync
    $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', ['orders' => [$orderData]])
        ->assertOk();

    expect(Order::where('uuid', $uuid)->count())->toBe(1)
        ->and((float) $this->beefPatty->fresh()->stock_level)->toEqual(49.0);

    // Duplicate Sync
    $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', ['orders' => [$orderData]])
        ->assertOk();

    // Must NOT create duplicate order and must NOT double-deduct raw stock
    expect(Order::where('uuid', $uuid)->count())->toBe(1)
        ->and((float) $this->beefPatty->fresh()->stock_level)->toEqual(49.0);
});

it('restores raw inventory when an offline cancellation/refund is synced', function () {
    $uuid = (string) Str::uuid();

    $itemPayload = [
        [
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'quantity'     => 3,
            'unit_price'   => 11.00,
            'vat_rate'     => 10.0,
            'subtotal'     => 33.00,
        ],
    ];

    $paymentPayload = [
        ['amount' => 33.00, 'method' => 'cash'],
    ];

    // 1. Initial sale (3 burgers = 3 patties deducted)
    $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', [
            'orders' => [
                [
                    'uuid'              => $uuid,
                    'subtotal_excl_vat' => 30.00,
                    'vat_amount'        => 3.00,
                    'total_incl_vat'    => 33.00,
                    'completed_at'      => now()->toIso8601String(),
                    'items'             => $itemPayload,
                    'payments'          => $paymentPayload,
                ],
            ],
        ])->assertOk();

    expect((float) $this->beefPatty->fresh()->stock_level)->toEqual(47.0);

    // 2. Sync cancellation with valid order items and payments (as sent by Dexie.js offline queue)
    $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', [
            'orders' => [
                [
                    'uuid'               => $uuid,
                    'subtotal_excl_vat'  => 30.00,
                    'vat_amount'         => 3.00,
                    'total_incl_vat'     => 33.00,
                    'completed_at'       => now()->toIso8601String(),
                    'status'             => 'cancelled',
                    'preparation_status' => 'cancelled',
                    'items'              => $itemPayload,
                    'payments'           => $paymentPayload,
                ],
            ],
        ])->assertOk();

    $order = Order::where('uuid', $uuid)->first();
    expect($order->status)->toBe('refunded')
        ->and($order->preparation_status)->toBe('cancelled')
        ->and((float) $this->beefPatty->fresh()->stock_level)->toEqual(50.0); // 🚀 Stock restored!
});

it('returns 422 validation error when required fields are omitted', function () {
    $this->actingAs($this->staff, 'sanctum')
        ->postJson('/api/v1/pos/orders/sync', [
            'orders' => [
                [
                    'subtotal_excl_vat' => 10.00, // Missing uuid, items, payments
                ],
            ],
        ])
        ->assertStatus(422)
        // 🚀 Inspect the 'details' key returned by OrderSyncController
        ->assertJsonValidationErrors(['orders.0.uuid', 'orders.0.items', 'orders.0.payments'], 'details');
});