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

       // Simple: return all categories and their active products (no store_id checks!)
        $menu = Category::with(['products' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        return response()->json($menu);
    }
}
