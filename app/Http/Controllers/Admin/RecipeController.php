<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * List all products to select which recipe to manage.
     */
    public function index()
    {
        $products = Product::with('category')->get();
        $user = auth()->user();
        $isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        return view('admin.recipes.index', compact('products', 'isAdmin'));
    }

    /**
     * Show the unified Recipe Builder screen for a specific product.
     */
    public function show($productId)
    {
        $product = Product::with('category')->findOrFail($productId);
        
        // Fetch all current ingredients inside this product's recipe
        $recipes = Recipe::where('product_id', $productId)->with('ingredient')->get();
        
        // Fetch all available ingredients for the dropdown
        $ingredients = Ingredient::orderBy('name', 'asc')->get();
        
        $user = auth()->user();
        $isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        return view('admin.recipes.show', compact('product', 'recipes', 'ingredients', 'isAdmin'));
    }

    /**
     * Add an ingredient and quantity to the product's recipe.
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        // Prevent duplicates: If the ingredient is already in the recipe, update its quantity.
        // Otherwise, create a new row.
        Recipe::updateOrCreate(
            [
                'product_id' => $productId,
                'ingredient_id' => $request->ingredient_id
            ],
            [
                'quantity' => $request->quantity
            ]
        );

        return redirect()->back()->with('success', 'Ingrédient enregistré dans la recette !');
    }

    /**
     * Remove an ingredient from the product's recipe.
     */
    public function destroy($productId, $recipeId)
    {
        $recipe = Recipe::where('product_id', $productId)->findOrFail($recipeId);
        $recipe->delete();

        return redirect()->back()->with('success', 'Ingrédient retiré de la recette.');
    }
}