<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\OptionGroup;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class ProductStepController extends Controller
{
    /**
     * List all products with their attached Kiosk Step counts
     */
    public function index()
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        $products = Product::with(['category', 'optionGroups'])->get();
        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        return view('admin.product_steps.index', compact('products', 'isAdmin', 'currencySymbol'));
    }

    /**
     * Show the Step Builder screen for a specific product
     */
    public function show($productId)
    {
        $product = Product::with(['category', 'optionGroups.options'])->findOrFail($productId);
        
        // Fetch all available option groups to select from
        $allOptionGroups = OptionGroup::orderBy('name', 'asc')->get();

        $isAdmin = auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('admin');

        return view('admin.product_steps.show', compact('product', 'allOptionGroups', 'isAdmin'));
    }

    /**
     * Attach or update an Option Group step for a product
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'option_group_id'            => 'required|exists:option_groups,id',
            'step_order'                 => 'required|integer|min:1|max:20',
            'free_choice_limit_override' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($productId);

        // Attach or update pivot data
        $product->optionGroups()->syncWithoutDetaching([
            $request->option_group_id => [
                'step_order'                 => $request->step_order,
                'free_choice_limit_override' => $request->filled('free_choice_limit_override') ? $request->free_choice_limit_override : null,
            ]
        ]);

        return redirect()->back()->with('success', 'Kiosk step attached to product successfully!');
    }

    /**
     * Remove an Option Group step from a product
     */
    public function destroy($productId, $optionGroupId)
    {
        $product = Product::findOrFail($productId);
        $product->optionGroups()->detach($optionGroupId);

        return redirect()->back()->with('success', 'Kiosk step removed from product.');
    }
}