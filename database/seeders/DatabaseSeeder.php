<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
use App\Models\Supplier;
use Carbon\Carbon;
use HasinHayder\Tyro\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Run Tyro's seeders silently
        Artisan::call('tyro:seed-all', ['--force' => true]);

        // Rename Spatie/Tyro's default 'super-admin' role slug to 'superadmin' to match layouts
        // $seededRole = Role::where('slug', 'super-admin')->first();
        // role slug auto
        // if ($seededRole) {
        //     $seededRole->update(['slug' => 'superadmin']);
        // }

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $AdminRole = Role::where('slug', 'admin')->first();
        
        $cashierRole = Role::firstOrCreate(
            ['slug' => 'cashier'],
            ['name' => 'Cashier']
        );

        // 2. Create your custom Super Admin (SaaS Owner)
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@burgerpalace.fr',
            'password' => Hash::make('adminpassword'), 
        ]);
        if ($superAdminRole) {
            $superadmin->assignRole($superAdminRole);
        }
        // 3. Create your custom Super Admin/manager (SaaS Owner) with a different email
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'manager@burgerpalace.fr',
            'password' => Hash::make('managerpassword'), 
        ]);
        if ($AdminRole) {
            $admin->assignRole($AdminRole);
        }

        // 4. Create a Cashier User (Using standard password)
        $cashier = User::create([
            'name' => 'Cashier One',
            'email' => 'cashier@burgerpalace.fr',
            'password' => Hash::make('password123'),
        ]);
        $cashier->assignRole($cashierRole);
           // ======================================================
        // 4. PRE-SEED FOUR SUPPLIERS (MODULE 3)
        // ======================================================
        $metro = Supplier::create([
            'name' => 'Metro Cash & Carry Bordeaux',
            'contact_name' => 'Jean Dupont',
            'email' => 'contact.bordeaux@metro.fr',
            'phone' => '0556112233',
        ]);

        $boulangerie = Supplier::create([
            'name' => 'Boulangerie de la Rose',
            'contact_name' => 'Marie Dubois',
            'email' => 'contact@boulangeriedelarose.fr',
            'phone' => '0556445566',
        ]);

        $pomona = Supplier::create([
            'name' => 'Pomona Gastronomie',
            'contact_name' => 'Pierre Martin',
            'email' => 'bordeaux@pomona.fr',
            'phone' => '0556778899',
        ]);

        $boissons = Supplier::create([
            'name' => 'Grossiste Boissons Atlantique',
            'contact_name' => 'Nicolas Bernard',
            'email' => 'commandes@boissonsatlantique.fr',
            'phone' => '0556223344',
        ]);

        // ======================================================
        // 5. PRE-SEED INGREDIENTS WITH REAL WHOLESALE PRICING
        // ======================================================
        $bun = Ingredient::create([
            'primary_supplier_id' => $boulangerie->id,
            'name' => 'Pain Burger (Bun)', 
            'stock_level' => 500.00, 
            'alert_level' => 50.00, 
            'unit' => 'unit'
        ]);

        $beef = Ingredient::create([
            'primary_supplier_id' => $metro->id,
            'name' => 'Steack Haché de Bœuf', 
            'stock_level' => 500.00, 
            'alert_level' => 50.00, 
            'unit' => 'unit'
        ]);

        $cheddar = Ingredient::create([
            'primary_supplier_id' => $pomona->id,
            'name' => 'Cheddar Tranche', 
            'stock_level' => 800.00, 
            'alert_level' => 80.00, 
            'unit' => 'unit'
        ]);

        $cokeCan = Ingredient::create([
            'primary_supplier_id' => $boissons->id,
            'name' => 'Canette Coca-Cola 33cl', 
            'stock_level' => 240.00, 
            'alert_level' => 48.00, 
            'unit' => 'unit'
        ]);

        $potatoes = Ingredient::create([
            'primary_supplier_id' => $pomona->id,
            'name' => 'Pomme de terre (Frites)', 
            'stock_level' => 100000.00, // 100 kg in grams
            'alert_level' => 20000.00, // 20 kg
            'unit' => 'g'
        ]);

        // ======================================================
        // 🍔 MULTIDIMENSIONAL CATALOG DATASET (91 Products)
        // ======================================================
        $menuData = [
            [
                'category' => 'Burgers Boeuf',
                'products' => [
                    ['name' => 'Single Cheeseburger', 'price' => 7.50, 'vat_rate' => 10.00],
                    ['name' => 'Double Cheeseburger', 'price' => 9.50, 'vat_rate' => 10.00],
                    ['name' => 'Triple Cheeseburger', 'price' => 11.50, 'vat_rate' => 10.00],
                    ['name' => 'Classic Bacon Burger', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Double Bacon Burger', 'price' => 11.90, 'vat_rate' => 10.00],
                    ['name' => 'BBQ Smokey Burger', 'price' => 9.20, 'vat_rate' => 10.00],
                    ['name' => 'Monster King Burger', 'price' => 13.50, 'vat_rate' => 10.00],
                    ['name' => 'Egg & Beef Burger', 'price' => 8.80, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Burgers Volaille & Poisson',
                'products' => [
                    ['name' => 'Crispy Chicken Burger', 'price' => 8.50, 'vat_rate' => 10.00],
                    ['name' => 'Grilled Chicken Burger', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Chicken Burger', 'price' => 9.00, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Bacon Ranch', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Classic Fish Filet', 'price' => 7.90, 'vat_rate' => 10.00],
                    ['name' => 'Double Fish Filet', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Honey Mustard Chicken', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Sweet Chili Chicken', 'price' => 8.80, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Burgers Veggie',
                'products' => [
                    ['name' => 'Green Garden Burger', 'price' => 8.20, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Avocado Veggie', 'price' => 9.50, 'vat_rate' => 10.00],
                    ['name' => 'Falafel Pita Burger', 'price' => 8.00, 'vat_rate' => 10.00],
                    ['name' => 'Portobello Mushroom', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Halloumi Honey Burger', 'price' => 9.20, 'vat_rate' => 10.00],
                    ['name' => 'Beyond Meat Classic', 'price' => 10.50, 'vat_rate' => 10.00],
                    ['name' => 'Tofu Teriyaki Burger', 'price' => 8.50, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Frites & Accompagnements',
                'products' => [
                    ['name' => 'Small French Fries', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Medium French Fries', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Large French Fries', 'price' => 4.50, 'vat_rate' => 10.00],
                    ['name' => 'Small Sweet Potatoes', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Large Sweet Potatoes', 'price' => 5.50, 'vat_rate' => 10.00],
                    ['name' => 'Loaded Cheese Fries', 'price' => 6.90, 'vat_rate' => 10.00],
                    ['name' => 'Onion Rings x6', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Onion Rings x12', 'price' => 5.90, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Nuggets & Wings',
                'products' => [
                    ['name' => 'Chicken Nuggets x4', 'price' => 2.90, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x6', 'price' => 3.90, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x9', 'price' => 5.50, 'vat_rate' => 10.00],
                    ['name' => 'Chicken Nuggets x20', 'price' => 10.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Chicken Wings x5', 'price' => 4.90, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Chicken Wings x10', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Mozzarella Sticks x5', 'price' => 4.50, 'vat_rate' => 10.00],
                    ['name' => 'Jalapeno Poppers x5', 'price' => 4.90, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Salades Fraîches',
                'products' => [
                    ['name' => 'Classic Caesar Salad', 'price' => 8.50, 'vat_rate' => 10.00],
                    ['name' => 'Crispy Caesar Salad', 'price' => 9.90, 'vat_rate' => 10.00],
                    ['name' => 'Greek Feta Salad', 'price' => 7.90, 'vat_rate' => 10.00],
                    ['name' => 'Quinoa Avocado Salad', 'price' => 8.90, 'vat_rate' => 10.00],
                    ['name' => 'Goat Cheese & Honey', 'price' => 8.80, 'vat_rate' => 10.00],
                    ['name' => 'Italian Caprese', 'price' => 8.20, 'vat_rate' => 10.00],
                    ['name' => 'Tuna Nicoise Salad', 'price' => 8.90, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Sauces',
                'products' => [
                    ['name' => 'Ketchup Cup', 'price' => 0.30, 'vat_rate' => 10.00],
                    ['name' => 'Mayonnaise Cup', 'price' => 0.30, 'vat_rate' => 10.00],
                    ['name' => 'Smokey BBQ Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Spicy Samurai Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Honey Mustard Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Garlic Herb Mayo', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Sweet Chili Dip', 'price' => 0.40, 'vat_rate' => 10.00],
                    ['name' => 'Warm Cheddar Sauce', 'price' => 1.00, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Desserts & Pâtisseries',
                'products' => [
                    ['name' => 'Chocolate Chip Cookie', 'price' => 2.00, 'vat_rate' => 5.50],
                    ['name' => 'Double Chocolate Muffin', 'price' => 3.00, 'vat_rate' => 5.50],
                    ['name' => 'Blueberry White Muffin', 'price' => 3.00, 'vat_rate' => 5.50],
                    ['name' => 'Apple Turnover', 'price' => 2.50, 'vat_rate' => 5.50],
                    ['name' => 'Fudge Chocolate Brownie', 'price' => 3.20, 'vat_rate' => 5.50],
                    ['name' => 'Lemon Meringue Slice', 'price' => 3.50, 'vat_rate' => 5.50],
                    ['name' => 'Nutella Glazed Donut', 'price' => 2.20, 'vat_rate' => 5.50],
                    ['name' => 'New York Cheesecake', 'price' => 4.00, 'vat_rate' => 5.50],
                ]
            ],
            [
                'category' => 'Glaces & Shakes',
                'products' => [
                    ['name' => 'Vanilla Soft Serve', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Chocolate Soft Serve', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'Caramel Sundae Cup', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Strawberry Sundae Cup', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Classic Vanilla Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Classic Choco Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Classic Strawberry Shake', 'price' => 4.20, 'vat_rate' => 10.00],
                    ['name' => 'Oreo Cookie Shake', 'price' => 4.90, 'vat_rate' => 10.00],
                ]
            ],
            [
                'category' => 'Boissons Gazeuses',
                'products' => [
                    ['name' => 'Coca-Cola Classic 33cl', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Coca-Cola Zero 33cl', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Fanta Orange 33cl', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Sprite Lemon 33cl', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Fuze Tea Peach 33cl', 'price' => 2.50, 'vat_rate' => 20.00],
                    ['name' => 'Orangina 33cl', 'price' => 2.60, 'vat_rate' => 20.00],
                    ['name' => 'Schweppes Tonic 33cl', 'price' => 2.60, 'vat_rate' => 20.00],
                    ['name' => 'Dr Pepper 33cl', 'price' => 2.70, 'vat_rate' => 20.00],
                ]
            ],
            [
                'category' => 'Eaux & Jus',
                'products' => [
                    ['name' => 'Evian Still Water 50cl', 'price' => 1.80, 'vat_rate' => 5.50],
                    ['name' => 'Badoit Sparkling 50cl', 'price' => 2.00, 'vat_rate' => 5.50],
                    ['name' => 'Tropicana Orange 25cl', 'price' => 2.80, 'vat_rate' => 20.00],
                    ['name' => 'Tropicana Apple 25cl', 'price' => 2.80, 'vat_rate' => 20.00],
                    ['name' => 'Fresh Lemonade 40cl', 'price' => 3.50, 'vat_rate' => 20.00],
                    ['name' => 'Tomato Juice 25cl', 'price' => 3.00, 'vat_rate' => 20.00],
                    ['name' => 'Coconut Water 33cl', 'price' => 3.20, 'vat_rate' => 5.50],
                ]
            ],
            [
                'category' => 'Café & Boissons Chaudes',
                'products' => [
                    ['name' => 'Espresso Shot', 'price' => 1.50, 'vat_rate' => 10.00],
                    ['name' => 'Double Espresso', 'price' => 2.20, 'vat_rate' => 10.00],
                    ['name' => 'Americano Black Coffee', 'price' => 2.00, 'vat_rate' => 10.00],
                    ['name' => 'Classic Cafe Latte', 'price' => 3.00, 'vat_rate' => 10.00],
                    ['name' => 'Cappuccino Cup', 'price' => 3.20, 'vat_rate' => 10.00],
                    ['name' => 'Classic Hot Chocolate', 'price' => 3.50, 'vat_rate' => 10.00],
                    ['name' => 'Organic Green Tea', 'price' => 2.50, 'vat_rate' => 10.00],
                    ['name' => 'English Breakfast Tea', 'price' => 2.50, 'vat_rate' => 10.00],
                ]
            ]
        ];

        // 6. Populate categories and products
        // Stores all product models mapping to automatically attach recipes in step 8
        $productModelMap = [];

        foreach ($menuData as $group) {
            $category = Category::create([
                'name' => $group['category']
            ]);

            foreach ($group['products'] as $productData) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'price' => $productData['price'],
                    'vat_rate' => $productData['vat_rate'],
                    'is_active' => true,
                ]);

                $productModelMap[$product->name] = $product;

                // ======================================================
                // 🚀 AUTOMATED RECIPE MAPPING ENGINE (MODULE 2)
                // ======================================================
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
                }

                elseif (str_contains($product->name, 'Fries') || str_contains($product->name, 'Frites')) {
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $potatoes->id, 'quantity' => 150.00]);
                }

                elseif (str_contains($product->name, 'Coca-Cola')) {
                    Recipe::create(['product_id' => $product->id, 'ingredient_id' => $cokeCan->id, 'quantity' => 1.00]);
                }
            }
        }

        // ======================================================
        // 🚀 7. PRE-SEED COMPLETED SUPPLIER INVOICE (PO #1001)
        // Highly realistic wholesale restaurant costs (Bordeaux) [1.1.2]
        // ======================================================
        $po = PurchaseOrder::create([
            'supplier_id' => $metro->id,
            'po_number' => 1001,
            'invoice_number' => 'FACT-2026-001',
            'status' => 'received',
            'total_cost' => 117.80, // (100 * 0.45) + (100 * 0.20) + (150 * 0.10) + (48 * 0.60)
            'received_at' => Carbon::now()->subDays(1),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'ingredient_id' => $beef->id, // Raw Beef Patty
            'quantity_ordered' => 100.00,
            'quantity_received' => 100.00,
            'unit_price' => 0.45, // 0.45 € per patty
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'ingredient_id' => $bun->id, // Buns
            'quantity_ordered' => 100.00,
            'quantity_received' => 100.00,
            'unit_price' => 0.20, // 0.20 € per bun
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'ingredient_id' => $cheddar->id, // Cheddar
            'quantity_ordered' => 150.00,
            'quantity_received' => 150.00,
            'unit_price' => 0.10, // 0.10 € per slice
        ]);
        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'ingredient_id' => $cokeCan->id, // Coca Cans
            'quantity_ordered' => 48.00,
            'quantity_received' => 48.00,
            'unit_price' => 0.60, // 0.60 € per can
        ]);

        // Increment stock levels in SQLite/MySQL for received items
        $beef->increment('stock_level', 100.00);
        $bun->increment('stock_level', 100.00);
        $cheddar->increment('stock_level', 150.00);
        $cokeCan->increment('stock_level', 48.00);

        // Save a corresponding Food Cost (COGS) Expense record
        Expense::create([
            'category' => 'food_cost',
            'description' => "Approvisionnement PO #1001 (Facture #FACT-2026-001)",
            'amount' => 117.80,
            'payment_method' => 'bank_transfer',
            'purchase_order_id' => $po->id,
            'paid_at' => Carbon::now()->subDays(1),
        ]);

        // ======================================================
        // 🚀 8. PRE-SEED NON-FOOD UTILITY EXPENSES (OPEX)
        // ======================================================
        // A. Monthly Rent Expense [1]
        Expense::create([
            'category' => 'rent',
            'description' => 'Loyer Commercial (Bordeaux)',
            'amount' => 1200.00,
            'payment_method' => 'bank_transfer',
            'paid_at' => Carbon::now()->startOfMonth(),
        ]);

        // B. Electricity Bill Expense [1]
        Expense::create([
            'category' => 'electricity',
            'description' => 'Facture Électricité EDF',
            'amount' => 200.00,
            'payment_method' => 'bank_transfer',
            'paid_at' => Carbon::now()->startOfMonth(),
        ]);

        // ======================================================
        // 🚀 9. NEW: PRE-SEED 150 COMPLETED SALES ORDERS (REVENUE)
        // Automatically calculates actual VAT splits and builds 
        // the cryptographic SHA-256 hash chains in real-time [1]!
        // ======================================================
        $this->command->info("Seeding 150 cryptographically secure sales orders...");

        $salesStartDate = Carbon::now()->subDays(5);
        $previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
        $sequenceNumber = 1;

        // Products we want to randomly sell
        $sellableProducts = [
            $productModelMap['Single Cheeseburger'],
            $productModelMap['Double Cheeseburger'],
            $productModelMap['Classic Bacon Burger'],
            $productModelMap['Small French Fries'],
            $productModelMap['Medium French Fries'],
            $productModelMap['Coca-Cola Classic 33cl'],
            $productModelMap['Evian Still Water 50cl'],
        ];

        for ($i = 0; $i < 150; $i++) {
            // Generate a random completed date over the last 5 days
            $completedAt = $salesStartDate->copy()->addMinutes(rand(10, 7200));

            // Randomly select 1 to 3 items for this order
            $orderItems = [];
            $subtotalExclVat = 0;
            $vatAmount = 0;
            $totalInclVat = 0;

            $numItems = rand(1, 3);
            $selectedProducts = array_rand($sellableProducts, $numItems);
            $selectedProductsList = is_array($selectedProducts) ? $selectedProducts : [$selectedProducts];

            foreach ($selectedProductsList as $prodIndex) {
                $prod = $sellableProducts[$prodIndex];
                $quantity = rand(1, 2);
                $itemTotalTtc = $prod->price * $quantity;
                $itemSubtotalHt = $itemTotalTtc / (1 + ($prod->vat_rate / 100));
                $itemVat = $itemTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount += $itemVat;
                $totalInclVat += $itemTotalTtc;

                $orderItems[] = [
                    'product_id' => $prod->id,
                    'product_name' => $prod->name,
                    'quantity' => $quantity,
                    'unit_price' => $prod->price,
                    'vat_rate' => $prod->vat_rate,
                    'subtotal' => $itemTotalTtc,
                ];
            }

            // 🚀 THE CRYPTO ENGINE: Generate mathematically secure SHA-256 hash chains! [1]
            $orderUuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
            $orderUuid = preg_replace_callback('/[xy]/', function ($matches) {
                $r = random_int(0, 15);
                $v = $matches[0] === 'x' ? $r : ($r & 0x3 | 0x8);
                return dechex($v);
            }, $orderUuid);

            // Format the string identically to both JS (tablet) and PHP (verifier) [1]
            $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            
            $currentHash = hash('sha256', $dataToHash);

            // Create Order
            $order = Order::create([
                'uuid' => $orderUuid,
                'user_id' => $cashier->id,
                'sequence_number' => $sequenceNumber,
                'subtotal_excl_vat' => $subtotalExclVat,
                'vat_amount' => $vatAmount,
                'total_incl_vat' => $totalInclVat,
                'hash' => $currentHash,
                'previous_hash' => $previousHash,
                'completed_at' => $completedAt,
            ]);

            // Create Order Items
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

                // Deduct stocks automatically! [1]
                $recipes = Recipe::where('product_id', $item['product_id'])->get();
                foreach ($recipes as $recipe) {
                    Ingredient::where('id', $recipe->ingredient_id)->decrement('stock_level', $item['quantity'] * $recipe->quantity);
                }
            }

            // Create Payment
            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalInclVat,
                'method' => rand(1, 100) > 40 ? 'card' : 'cash', // 60% Card, 40% Cash
            ]);

            // Move the chain forward
            $previousHash = $currentHash;
            $sequenceNumber++;
        }

        // 9. Clean up Tyro default admin account
        User::where('email', 'admin@tyro.project')->delete();
    }
}