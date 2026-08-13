<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * List all products with their Food Cost % and Profit Margin
     */
    public function index()
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        $products = Product::with(['category', 'recipes.ingredient'])->get();
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        return view('admin.recipes.index', compact('products', 'isAdmin', 'currencySymbol'));
    }

    /**
     * Show the Recipe Builder & Costing screen for a specific product
     */
    public function show($productId)
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        $product = Product::with('category')->findOrFail($productId);
        
        // Fetch all current ingredients in this product's recipe
        $recipes = Recipe::where('product_id', $productId)->with('ingredient')->get();
        
        // Fetch all available ingredients
        $ingredients = Ingredient::orderBy('name', 'asc')->get();
        
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        // Calculate Total Theoretical Food Cost (COGS)
        $totalFoodCost = $recipes->sum(function ($recipe) {
            $costPerUnit = (float) ($recipe->ingredient->cost_per_unit ?? 0.0);
            return $recipe->quantity * $costPerUnit;
        });

        // Calculate Selling Price HT and Profit Margin
        $vatRate = (float) ($product->vat_rate ?? 10.0);
        $sellingPriceHt = $product->price / (1 + ($vatRate / 100));
        $profitMargin = $sellingPriceHt - $totalFoodCost;
        $foodCostPercentage = $sellingPriceHt > 0 ? round(($totalFoodCost / $sellingPriceHt) * 100, 1) : 0;

        return view('admin.recipes.show', compact(
            'product',
            'recipes',
            'ingredients',
            'isAdmin',
            'currencySymbol',
            'totalFoodCost',
            'sellingPriceHt',
            'profitMargin',
            'foodCostPercentage'
        ));
    }

    /**
     * Add or update an ingredient in the recipe
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity'      => 'required|numeric|min:0.0001',
        ]);

        Recipe::updateOrCreate(
            [
                'product_id'    => $productId,
                'ingredient_id' => $request->ingredient_id,
            ],
            [
                'quantity' => $request->quantity,
            ]
        );

        return redirect()->back()->with('success', 'Ingredient added to recipe successfully!');
    }

    /**
     * Remove an ingredient from the recipe
     */
    public function destroy($productId, $recipeId)
    {
        $recipe = Recipe::where('product_id', $productId)->findOrFail($recipeId);
        $recipe->delete();

        return redirect()->back()->with('success', 'Ingredient removed from recipe.');
    }
}