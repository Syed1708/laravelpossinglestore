<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Recipe;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ======================================================
        // 1. ROLES & USERS (3 USERS IN ENGLISH)
        // ======================================================
        Artisan::call('tyro:seed-all', ['--force' => true]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $cashierRole = Role::firstOrCreate(['slug' => 'cashier'], ['name' => 'Cashier']);

        // User 1: Super Admin
        $superadmin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@burgerpalace.com',
            'password' => Hash::make('adminpassword'),
        ]);
        if ($superAdminRole) {
            $superadmin->assignRole($superAdminRole);
        }

        // User 2: General Manager
        $admin = User::create([
            'name'     => 'General Manager',
            'email'    => 'manager@burgerpalace.com',
            'password' => Hash::make('managerpassword'),
        ]);
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        // User 3: Cashier
        $cashier = User::create([
            'name'     => 'Cashier One',
            'email'    => 'cashier@burgerpalace.com',
            'password' => Hash::make('password123'),
        ]);
        $cashier->assignRole($cashierRole);

        // Ensure default store settings exist
        StoreSetting::getSettings();

        // ======================================================
        // 2. SUPPLIERS (ENGLISH)
        // ======================================================
        $metro = Supplier::create([
            'name'         => 'Metropolitan Wholesale Foods',
            'contact_name' => 'John Miller',
            'email'        => 'orders@metrowholesale.com',
            'phone'        => '+1-555-019-2834',
        ]);

        $bakery = Supplier::create([
            'name'         => 'Golden Crust Artisan Bakery',
            'contact_name' => 'Sarah Jenkins',
            'email'        => 'deliveries@goldencrustbakery.com',
            'phone'        => '+1-555-014-5566',
        ]);

        $dairy = Supplier::create([
            'name'         => 'Prime Dairy & Produce Co.',
            'contact_name' => 'Michael Chang',
            'email'        => 'orders@primedairyproduce.com',
            'phone'        => '+1-555-017-8899',
        ]);

        $beverages = Supplier::create([
            'name'         => 'National Beverage Distributors',
            'contact_name' => 'David Wilson',
            'email'        => 'sales@nationalbeverage.com',
            'phone'        => '+1-555-012-3344',
        ]);

        // ======================================================
        // 3. INGREDIENTS (ENGLISH)
        // Large initial stock to support 5,000 sales orders
        // ======================================================
        $bun = Ingredient::create([
            'primary_supplier_id' => $bakery->id,
            'name'                => 'Brioche Burger Bun',
            'stock_level'         => 30000.00,
            'alert_level'         => 250.00,
            'unit'                => 'unit',
        ]);

        $beef = Ingredient::create([
            'primary_supplier_id' => $metro->id,
            'name'                => 'Fresh Ground Beef Patty (150g)',
            'stock_level'         => 30000.00,
            'alert_level'         => 250.00,
            'unit'                => 'unit',
        ]);

        $cheddar = Ingredient::create([
            'primary_supplier_id' => $dairy->id,
            'name'                => 'Aged Sliced Cheddar',
            'stock_level'         => 35000.00,
            'alert_level'         => 300.00,
            'unit'                => 'unit',
        ]);

        $cokeCan = Ingredient::create([
            'primary_supplier_id' => $beverages->id,
            'name'                => 'Coca-Cola Can (330ml)',
            'stock_level'         => 20000.00,
            'alert_level'         => 150.00,
            'unit'                => 'unit',
        ]);

        $potatoes = Ingredient::create([
            'primary_supplier_id' => $dairy->id,
            'name'                => 'Russet Potatoes (Fries)',
            'stock_level'         => 5000000.00, // in grams
            'alert_level'         => 50000.00,
            'unit'                => 'g',
        ]);

        // ======================================================
        // 4. MULTIDIMENSIONAL CATALOG (100% ENGLISH)
        // ======================================================
        $menuData = [
            [
                'category' => 'Beef Burgers',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Single Cheeseburger', 'price' => 7.50, 'vat_rate' => 10.00],
                    ['name' => 'Double Cheeseburger', 'price' => 9.50, 'vat_rate' => 10.00],
                    ['name' => 'Triple Cheeseburger', 'price' => 11.50, 'vat_rate' => 10.00],
                    ['name' => 'Classic Bacon Burger', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Double Bacon Burger', 'price' => 11.90, 'vat_rate' => 10.00],
                    ['name' => 'Smoky BBQ Burger', 'price' => 9.20, 'vat_rate' => 10.00],
                    ['name' => 'Monster King Burger', 'price' => 13.50, 'vat_rate' => 10.00],
                    ['name' => 'Fried Egg & Beef Burger', 'price' => 8.80, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Chicken & Fish Burgers',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Crispy Chicken Burger', 'price' => 8.50, 'vat_rate' => 10.00],
                    ['name' => 'Grilled Chicken Burger', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Buffalo Chicken Burger', 'price' => 9.00, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Bacon Ranch', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Classic Fish Fillet', 'price' => 7.90, 'vat_rate' => 10.00],
                    ['name' => 'Double Fish Fillet', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Honey Mustard Chicken', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Sweet Chili Chicken', 'price' => 8.80, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Vegetarian & Vegan Burgers',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Green Garden Veggie Burger', 'price' => 8.20, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Avocado Vegan Burger', 'price' => 9.50, 'vat_rate' => 10.00],
                    ['name' => 'Falafel Pita Burger', 'price' => 8.00, 'vat_rate' => 10.00],
                    ['name' => 'Portobello Mushroom Burger', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Halloumi & Honey Burger', 'price' => 9.20, 'vat_rate' => 10.00],
                    ['name' => 'Beyond Meat Classic', 'price' => 10.50, 'vat_rate' => 10.00],
                    ['name' => 'Teriyaki Tofu Burger', 'price' => 8.50, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Fries & Sides',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Small French Fries', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Medium French Fries', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Large French Fries', 'price' => 4.50, 'vat_rate' => 10.00],
                    ['name' => 'Small Sweet Potato Fries', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Large Sweet Potato Fries', 'price' => 5.50, 'vat_rate' => 10.00],
                    ['name' => 'Loaded Cheese & Bacon Fries', 'price' => 6.90, 'vat_rate' => 10.00],
                    ['name' => 'Crispy Onion Rings x6', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Crispy Onion Rings x12', 'price' => 5.90, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Nuggets & Wings',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Chicken Nuggets x4', 'price' => 2.90, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x6', 'price' => 3.90, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x9', 'price' => 5.50, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x20', 'price' => 10.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Chicken Wings x5', 'price' => 4.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Chicken Wings x10', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Mozzarella Sticks x5', 'price' => 4.50, 'vat_rate' => 10.00],
                    ['name' => 'Jalapeno Poppers x5', 'price' => 4.90, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Fresh Salads',
                'show_kds' => true,
                'products' => [
                    ['name' => 'Classic Caesar Salad', 'price' => 8.50, 'vat_rate' => 10.00],
                    ['name' => 'Crispy Chicken Caesar', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Greek Feta & Olive Salad', 'price' => 7.90, 'vat_rate' => 10.00],
                    ['name' => 'Quinoa & Avocado Salad', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Goat Cheese & Honey Salad', 'price' => 8.80, 'vat_rate' => 10.00],
                    ['name' => 'Caprese Mozzarella Salad', 'price' => 8.20, 'vat_rate' => 10.00],
                    ['name' => 'Tuna Nicoise Bowl', 'price' => 8.90, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Dips & Sauces',
                'show_kds' => false,
                'products' => [
                    ['name' => 'Ketchup Dip Cup', 'price' => 0.30, 'vat_rate' => 10.00],
                    ['name' => 'Mayonnaise Dip Cup', 'price' => 0.30, 'vat_rate' => 10.00],
                    ['name' => 'Smoky BBQ Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Samurai Sauce', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Honey Mustard Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Garlic Herb Mayo', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Sweet Chili Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Warm Melted Cheddar Dip', 'price' => 1.00, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Desserts & Bakery',
                'show_kds' => false, // 👈 Never shown on chef hot screen
                'products' => [
                    ['name' => 'Chocolate Chip Cookie', 'price' => 2.00, 'vat_rate' => 5.50],
                    ['name' => 'Double Chocolate Muffin', 'price' => 3.00, 'vat_rate' => 5.50],
                    ['name' => 'Blueberry Crumble Muffin', 'price' => 3.00, 'vat_rate' => 5.50],
                    ['name' => 'Warm Apple Turnover', 'price' => 2.50, 'vat_rate' => 5.50],
                    ['name' => 'Fudge Chocolate Brownie', 'price' => 3.20, 'vat_rate' => 5.50],
                    ['name' => 'Lemon Meringue Tartlet', 'price' => 3.50, 'vat_rate' => 5.50],
                    ['name' => 'Nutella Glazed Donut', 'price' => 2.20, 'vat_rate' => 5.50],
                    ['name' => 'New York Cheesecake Slice', 'price' => 4.00, 'vat_rate' => 5.50],
                ],
            ],
            [
                'category' => 'Ice Cream & Shakes',
                'show_kds' => false,
                'products' => [
                    ['name' => 'Vanilla Soft Serve Cone', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Chocolate Soft Serve Cone', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Caramel Sundae Cup', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Strawberry Sundae Cup', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Classic Vanilla Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Classic Chocolate Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Classic Strawberry Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Oreo Cookies & Cream Shake', 'price' => 4.90, 'vat_rate' => 10.00],
                ],
            ],
            [
                'category' => 'Soft Drinks & Sodas',
                'show_kds' => false,
                'products' => [
                    ['name' => 'Coca-Cola Classic 330ml', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Coca-Cola Zero 330ml', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Fanta Orange 330ml', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Sprite Lemon-Lime 330ml', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Fuze Iced Tea Peach 330ml', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Sparkling Orangina 330ml', 'price' => 2.60, 'vat_rate' => 20.00],
                    ['name' => 'Schweppes Indian Tonic 330ml', 'price' => 2.60, 'vat_rate' => 20.00],
                    ['name' => 'Dr Pepper Classic 330ml', 'price' => 2.70, 'vat_rate' => 20.00],
                ],
            ],
            [
                'category' => 'Water & Juices',
                'show_kds' => false,
                'products' => [
                    ['name' => 'Still Spring Water 500ml', 'price' => 1.80, 'vat_rate' => 5.50],
                    ['name' => 'Sparkling Mineral Water 500ml', 'price' => 2.00, 'vat_rate' => 5.50],
                    ['name' => 'Tropicana Pure Orange 250ml', 'price' => 2.80, 'vat_rate' => 20.00],
                    ['name' => 'Tropicana Crisp Apple 250ml', 'price' => 2.80, 'vat_rate' => 20.00],
                    ['name' => 'Homemade Mint Lemonade 400ml', 'price' => 3.50, 'vat_rate' => 20.00],
                    ['name' => 'Spiced Tomato Juice 250ml', 'price' => 3.00, 'vat_rate' => 20.00],
                    ['name' => 'Pure Organic Coconut Water', 'price' => 3.20, 'vat_rate' => 5.50],
                ],
            ],
            [
                'category' => 'Coffee & Hot Drinks',
                'show_kds' => false,
                'products' => [
                    ['name' => 'Single Espresso Shot', 'price' => 1.50, 'vat_rate' => 10.00],
                    ['name' => 'Double Espresso Shot', 'price' => 2.20, 'vat_rate' => 10.00],
                    ['name' => 'Americano Black Coffee', 'price' => 2.00, 'vat_rate' => 10.00],
                    ['name' => 'Cafe Latte', 'price' => 3.00, 'vat_rate' => 10.00],
                    ['name' => 'Creamy Cappuccino', 'price' => 3.20, 'vat_rate' => 10.00],
                    ['name' => 'Hot Chocolate with Marshmallows', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Organic Jasmine Green Tea', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'English Breakfast Tea', 'price' => 2.50, 'vat_rate' => 10.00],
                ],
            ],
        ];

        $allProductsList = [];

        foreach ($menuData as $group) {
            $category = Category::create([
                'name'             => $group['category'],
                'show_on_chef_kds' => $group['show_kds'],
            ]);

            foreach ($group['products'] as $productData) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name'        => $productData['name'],
                    'price'       => $productData['price'],
                    'vat_rate'    => $productData['vat_rate'],
                    'is_active'   => true,
                ]);

                $allProductsList[] = $product;

                // Recipe mapping engine
                if (str_contains($category->name, 'Burgers')) {
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $bun->id, 'quantity' => 1.00]);
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $cheddar->id, 'quantity' => 1.00]);

                    $pattyQty = 1.00;
                    if (str_contains($product->name, 'Double')) {
                        $pattyQty = 2.00;
                    } elseif (str_contains($product->name, 'Triple') || str_contains($product->name, 'Monster')) {
                        $pattyQty = 3.00;
                    }

                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $beef->id, 'quantity' => $pattyQty]);
                } elseif (str_contains($product->name, 'Fries')) {
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $potatoes->id, 'quantity' => 150.00]);
                } elseif (str_contains($product->name, 'Coca-Cola')) {
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $cokeCan->id, 'quantity' => 1.00]);
                }
            }
        }

        // ======================================================
        // 5. SUPPLIER PURCHASES & OVERHEAD EXPENSES (ENGLISH)
        // ======================================================
        $po = PurchaseOrder::create([
            'supplier_id'    => $metro->id,
            'po_number'      => 1001,
            'invoice_number' => 'INV-2026-001',
            'status'         => 'received',
            'total_cost'     => 117.80,
            'received_at'    => Carbon::now()->subDays(1),
        ]);

        Expense::create([
            'category'          => 'food_cost',
            'description'       => 'Wholesale Meat & Produce PO #1001',
            'amount'            => 117.80,
            'payment_method'    => 'bank_transfer',
            'purchase_order_id' => $po->id,
            'paid_at'           => Carbon::now()->subDays(1),
        ]);

        Expense::create([
            'category'       => 'rent',
            'description'    => 'Monthly Commercial Restaurant Lease',
            'amount'         => 1800.00,
            'payment_method' => 'bank_transfer',
            'paid_at'        => Carbon::now()->startOfMonth(),
        ]);

        Expense::create([
            'category'       => 'electricity',
            'description'    => 'Electric & Gas Utilities Bill',
            'amount'         => 350.00,
            'payment_method' => 'bank_transfer',
            'paid_at'        => Carbon::now()->startOfMonth(),
        ]);

        // ======================================================
        // 6. 5,000 ORDERS (90-DAY PROGRESSION, 100% ENGLISH)
        // ======================================================
        $totalOrdersToSeed = 5000;
        $this->command->info("⚡ Fast-seeding {$totalOrdersToSeed} cryptographically chained orders across 90 days...");

        $customerNames = [
            'James Wilson', 'Emma Watson', 'Oliver Smith', 'Charlotte Brown', 'Liam Johnson',
            'Amelia Davis', 'Noah Miller', 'Sophia Taylor', 'Lucas Anderson', 'Mia Thomas',
            'Ethan White', 'Harper Harris', 'Benjamin Martin', 'Evelyn Clark', 'Alexander Lewis',
            'Abigail Walker', 'Daniel Hall', 'Emily Allen', 'Henry Young', 'Ella King',
            'William Wright', 'Aria Scott', 'Mason Green', 'Scarlett Adams', 'Logan Baker',
            'Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5', 'Table 6', 'Table 7', 'Table 8',
            'Counter Customer', 'Online Customer', 'VIP Booth 1', 'Patio Table 12'
        ];

        $orderTypes = ['dine_in', 'takeaway', 'click_and_collect'];
        $notesPool = [null, null, null, ['No onions'], ['Extra cheese'], ['Sauce on the side'], ['Well done'], ['No pickles'], ['Extra crispy']];

        $currentTimeTracker = Carbon::now('UTC')->subDays(90)->startOfDay();
        $previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
        $nowUtc = Carbon::now('UTC');

        DB::beginTransaction();

        for ($seq = 1; $seq <= $totalOrdersToSeed; $seq++) {
            // Keep timestamps moving forward chronologically
            $currentTimeTracker->addMinutes(rand(12, 38));

            // Anchor the last 60 orders firmly into "Today"
            if ($seq > ($totalOrdersToSeed - 60) && $currentTimeTracker->lt($nowUtc->copy()->startOfDay())) {
                $currentTimeTracker = $nowUtc->copy()->startOfDay()->addMinutes(($seq - ($totalOrdersToSeed - 60)) * 10);
            }

            // Never exceed the current minute
            if ($currentTimeTracker->gt($nowUtc)) {
                $currentTimeTracker = $nowUtc->copy()->subMinutes(rand(1, 30));
            }

            $orderTime = $currentTimeTracker->copy();

            $orderItems = [];
            $subtotalExclVat = 0.0;
            $vatAmount       = 0.0;
            $totalInclVat    = 0.0;

            $numItems = rand(1, 4);
            for ($k = 0; $k < $numItems; $k++) {
                $product = $allProductsList[array_rand($allProductsList)];
                $qty = rand(1, 2);
                $itemTtc = $product->price * $qty;
                $itemHt  = $itemTtc / (1 + ($product->vat_rate / 100));
                $itemVat = $itemTtc - $itemHt;

                $subtotalExclVat += $itemHt;
                $vatAmount       += $itemVat;
                $totalInclVat    += $itemTtc;

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $qty,
                    'unit_price'   => $product->price,
                    'vat_rate'     => $product->vat_rate,
                    'subtotal'     => $itemTtc,
                    'item_status'  => $seq > ($totalOrdersToSeed - 10) ? 'pending' : 'done',
                    'notes'        => $notesPool[array_rand($notesPool)],
                ];
            }

            // Cryptographic SHA-256 Hash Chaining
            $dataToHash = "{$seq}|"
                . number_format($subtotalExclVat, 2, '.', '') . '|'
                . number_format($vatAmount, 2, '.', '') . '|'
                . number_format($totalInclVat, 2, '.', '') . '|'
                . $orderTime->format('Y-m-d\TH:i:s\Z') . '|'
                . $previousHash;

            $currentHash = hash('sha256', $dataToHash);

            // Active preparation statuses for today's live testing
            $status = 'completed';
            $prepStatus = 'delivered';

            if ($seq > ($totalOrdersToSeed - 15)) {
                $prepStatus = 'pending';
                $status = 'completed';
            } elseif ($seq > ($totalOrdersToSeed - 30)) {
                $prepStatus = 'preparing';
                $status = 'completed';
            } elseif ($seq > ($totalOrdersToSeed - 45)) {
                $prepStatus = 'ready';
                $status = 'completed';
            }

            // Create Order
            $order = Order::create([
                'uuid'               => (string) Str::uuid(),
                'user_id'            => $cashier->id,
                'customer_name'      => $customerNames[array_rand($customerNames)],
                'order_type'         => $orderTypes[array_rand($orderTypes)],
                'sequence_number'    => $seq,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                'vat_amount'         => round($vatAmount, 2),
                'total_incl_vat'     => round($totalInclVat, 2),
                'hash'               => $currentHash,
                'previous_hash'      => $previousHash,
                'completed_at'       => $orderTime,
                'created_at'         => $orderTime,
                'updated_at'         => $orderTime,
                'preparation_status' => $prepStatus,
                'status'             => $status,
            ]);

            // Create Items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Create Payment
            $order->payments()->create([
                'amount'     => round($totalInclVat, 2),
                'method'     => rand(1, 100) > 40 ? 'card' : 'cash',
                'created_at' => $orderTime,
                'updated_at' => $orderTime,
            ]);

            $previousHash = $currentHash;

            if ($seq % 1000 === 0) {
                $this->command->line("  → Seeded {$seq} / {$totalOrdersToSeed} orders...");
            }
        }

        // ======================================================
        // 7. SYNCHRONIZE SEQUENCE COUNTERS
        // ======================================================
        DB::table('counters')->updateOrInsert(
            ['key' => 'order_sequence'],
            [
                'value'      => $totalOrdersToSeed,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::commit();

        User::where('email', 'admin@tyro.project')->delete();

        $this->command->info("🎉 Completed! {$totalOrdersToSeed} orders seeded in English and ready for testing.");
    }
}