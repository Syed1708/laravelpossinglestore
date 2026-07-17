<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Get the active menu (categories and active products) for the cashier's store.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->store_id) {
            return response()->json([
                'error' => 'User is not assigned to a store.'
            ], 403);
        }

        // Fetch categories and only eager load active products
        $menu = Category::where('store_id', $user->store_id)
            ->with(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json($menu);
    }
}
