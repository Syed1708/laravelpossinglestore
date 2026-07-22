<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class MenuEngineeringController extends Controller
{
    /**
     * Analyze and display product profitability, food costs, and gross margins.
     */
    public function index()
    {
        $products = Product::with('category')->get();
        $user = auth()->user();
        $isAdmin = $user->hasRole('superadmin') || $user->hasRole('admin');

        $analyzedProducts = [];

        foreach ($products as $product) {
            $totalFoodCost = 0;
            
            // Fetch all recipe components for this product
            $recipeItems = DB::table('recipes')
                ->where('product_id', $product->id)
                ->get();

            foreach ($recipeItems as $item) {
                // 🚀 DYNAMIC COST LOOKUP:
                // Find the unit price paid in the most recent RECEIVED delivery from suppliers
                $latestPrice = DB::table('purchase_order_items')
                    ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                    ->where('purchase_order_items.ingredient_id', $item->ingredient_id)
                    ->where('purchase_orders.status', 'received')
                    ->latest('purchase_orders.received_at')
                    ->value('purchase_order_items.unit_price');

                // Fallback to a default estimated cost of 1.00 € if no supplier invoices have been received yet
                if (is_null($latestPrice)) {
                    $latestPrice = 1.00; 
                }

                $itemCost = $item->quantity * $latestPrice;
                $totalFoodCost += $itemCost;
            }

            // French Accounting Math: Convert displayed Price (TTC) to Net Revenue (HT)
            $priceTtc = $product->price;
            $priceHt = $priceTtc / (1 + ($product->vat_rate / 100));
            
            // Calculate Gross Margin (Marge Brute = Revenue HT - Food Cost)
            $grossMargin = $priceHt - $totalFoodCost;
            $marginPercentage = $priceHt > 0 ? ($grossMargin / $priceHt) * 100 : 0;

            $analyzedProducts[] = [
                'name' => $product->name,
                'category' => $product->category->name ?? 'N/A',
                'price_ttc' => $priceTtc,
                'price_ht' => $priceHt,
                'food_cost' => $totalFoodCost,
                'margin_euros' => $grossMargin,
                'margin_percentage' => $marginPercentage,
            ];
        }

        return view('admin.menu_engineering.index', [
            'products' => $analyzedProducts,
            'isAdmin' => $isAdmin
        ]);
    }
}