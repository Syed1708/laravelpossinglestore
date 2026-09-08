<?php

use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Order;
use App\Services\Inventory\StockService;
use App\Services\Fiscal\FiscalLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

// 🚀 Automatically runs migrations for the test suite
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name'             => 'Burgers',
        'show_on_chef_kds' => true,
    ]);

    $this->beefPatty = Ingredient::create([
        'name'        => 'Beef Patty 150g',
        'stock_level' => 100.0,
        'alert_level' => 10.0,
        'unit'        => 'unit',
    ]);

    $this->burger = Product::create([
        'name'        => 'Double Smash Burger',
        'price'       => 12.50,
        'vat_rate'    => 10.0,
        'is_active'   => true,
        'category_id' => $this->category->id,
    ]);

    Recipe::create([
        'product_id'    => $this->burger->id,
        'ingredient_id' => $this->beefPatty->id,
        'quantity'      => 2,
    ]);
});

test('it atomically deducts stock exactly once via StockService', function () {
    $stockService = app(StockService::class);

    // Place an order for 3 burgers (3 burgers * 2 patties = 6 patties deducted)
    $stockService->decrementStockForItems([
        ['product_id' => $this->burger->id, 'quantity' => 3],
    ]);

    $this->beefPatty->refresh();
    expect((float) $this->beefPatty->stock_level)->toBe(94.0); // 100 - 6 = 94
});

test('it generates unbroken NF525 cryptographic hash chains', function () {
    $fiscalService = app(FiscalLedgerService::class);

    // Order 1
    $sig1 = $fiscalService->generateSignature(1, 10.00, 1.00, 11.00);
    $order1 = Order::create([
        'uuid'              => (string) \Illuminate\Support\Str::uuid(),
        'sequence_number'   => 1,
        'subtotal_excl_vat' => 10.00,
        'vat_amount'        => 1.00,
        'total_incl_vat'    => 11.00,
        'hash'              => $sig1['hash'],
        'previous_hash'     => $sig1['previous_hash'],
        'completed_at'      => $sig1['completed_at'],
        'status'            => 'completed',
    ]);

    // Order 2 (Should chain to Order 1's hash)
    $sig2 = $fiscalService->generateSignature(2, 20.00, 2.00, 22.00);
    expect($sig2['previous_hash'])->toBe($order1->hash);

    // Verify audit command returns 0 (Clean Audit)
    $this->artisan('pos:verify-chain')->assertExitCode(0);
});