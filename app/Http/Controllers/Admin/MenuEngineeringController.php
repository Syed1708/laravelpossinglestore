<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MenuEngineeringController extends Controller
{
    public function index(Request $request)
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        // 1. Calculate Menu Engineering Matrix for last 30 days
        $startDate = Carbon::now('Europe/Paris')->subDays(30)->startOfDay();
        $endDate   = Carbon::now('Europe/Paris')->endOfDay();

        $products = Product::with(['recipes.ingredient', 'category'])->where('is_active', true)->get();

        $matrix = [];
        $totalVolumeSold = 0;
        $totalProfitGenerated = 0;

        foreach ($products as $product) {
            // Volume Sold
            $quantitySold = OrderItem::where('product_id', $product->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('order', fn($q) => $q->whereNotIn('status', ['cancelled', 'refunded']))
                ->sum('quantity');

            // Recipe Cost (COGS)
            $recipeCost = $product->recipes->sum(function ($r) {
                return $r->quantity * ($r->ingredient->cost_per_unit ?? 0);
            });

            $sellingPriceHt = $product->price / (1 + (($product->vat_rate ?? 10) / 100));
            $unitMargin = $sellingPriceHt - $recipeCost;
            $totalMargin = $unitMargin * $quantitySold;

            $totalVolumeSold += $quantitySold;
            $totalProfitGenerated += $totalMargin;

            $matrix[] = [
                'id'            => $product->id,
                'name'          => $product->name,
                'category'      => $product->category->name ?? 'Uncategorized',
                'selling_price' => $product->price,
                'recipe_cost'   => round($recipeCost, 2),
                'unit_margin'   => round($unitMargin, 2),
                'quantity_sold' => (int) $quantitySold,
                'total_margin'  => round($totalMargin, 2),
            ];
        }

        // Calculate Average Thresholds for Quadrant Placement
        $avgVolume = count($matrix) > 0 ? ($totalVolumeSold / count($matrix)) : 0;
        $avgMargin = count($matrix) > 0 ? ($totalProfitGenerated / max(1, $totalVolumeSold)) : 0;

        // Categorize into Quadrants
        $stars = [];
        $plowhorses = [];
        $puzzles = [];
        $dogs = [];

        foreach ($matrix as &$item) {
            $isHighVolume = $item['quantity_sold'] >= $avgVolume;
            $isHighMargin = $item['unit_margin'] >= $avgMargin;

            if ($isHighVolume && $isHighMargin) {
                $item['quadrant'] = 'star';
                $stars[] = $item;
            } elseif ($isHighVolume && !$isHighMargin) {
                $item['quadrant'] = 'plowhorse';
                $plowhorses[] = $item;
            } elseif (!$isHighVolume && $isHighMargin) {
                $item['quadrant'] = 'puzzle';
                $puzzles[] = $item;
            } else {
                $item['quadrant'] = 'dog';
                $dogs[] = $item;
            }
        }

        return view('admin.menu_engineering.index', compact(
            'currencySymbol',
            'stars',
            'plowhorses',
            'puzzles',
            'dogs',
            'avgVolume',
            'avgMargin',
            'totalVolumeSold',
            'totalProfitGenerated'
        ));
    }
}