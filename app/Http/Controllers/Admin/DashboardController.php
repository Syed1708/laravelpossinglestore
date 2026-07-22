<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dynamic dashboard with real-time date filtering.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        // 1. Determine active filter type (default: today)
        $filterType = $request->input('filter_type', 'today');

        if ($filterType === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($filterType === 'custom' && $request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        } else {
            $filterType = 'today';
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }

        // 2. Calculate Revenue (HT & TTC) for Selected Period
        $revenue = Order::whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(total_incl_vat), 0) as total_ttc,
                COALESCE(SUM(subtotal_excl_vat), 0) as total_ht,
                COALESCE(SUM(vat_amount), 0) as total_tva
            ')
            ->first();

        // 3. Calculate Cost of Goods Sold (COGS - Food Cost) for Selected Period
        $foodCost = Expense::where('category', 'food_cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // 4. Calculate Operating Expenses (OPEX) for Selected Period
        $operatingCost = Expense::where('category', '!=', 'food_cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // 5. Calculate Net Profit
        $netProfit = $revenue->total_ht - ($foodCost + $operatingCost);

        // 6. Get the 10 most recent synced orders in this period
        $recentOrders = Order::whereBetween('completed_at', [$startDate, $endDate])
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'revenue' => $revenue,
            'foodCost' => $foodCost,
            'operatingCost' => $operatingCost,
            'netProfit' => $netProfit,
            'recentOrders' => $recentOrders,
            'isAdmin' => $isAdmin,
            'filterType' => $filterType,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);
    }
}