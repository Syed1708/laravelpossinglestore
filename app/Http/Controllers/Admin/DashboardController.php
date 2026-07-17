<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Define Today bounds
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        // 1. Calculate HT / TVA / TTC Totals
        // (Automatically scoped to the logged-in user's store_id!)
        $totals = Order::whereBetween('completed_at', [$todayStart, $todayEnd])
            ->selectRaw('
                COALESCE(SUM(total_incl_vat), 0) as total_ttc,
                COALESCE(SUM(subtotal_excl_vat), 0) as total_ht,
                COALESCE(SUM(vat_amount), 0) as total_tva
            ')
            ->first();

        // 2. Calculate Payment Method Distributions
        $payments = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$todayStart, $todayEnd])
            ->select('payments.method', DB::raw('SUM(payments.amount) as total_amount'))
            ->groupBy('payments.method')
            ->get();

        // 3. Calculate VAT Breakdown per French bracket (5.5%, 10%, 20%)
        $vatBreakdown = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$todayStart, $todayEnd])
            ->select(
                'order_items.vat_rate',
                DB::raw('SUM(order_items.subtotal) as total_ttc'),
                DB::raw('SUM(order_items.subtotal - (order_items.subtotal / (1 + (order_items.vat_rate / 100)))) as collected_vat')
            )
            ->groupBy('order_items.vat_rate')
            ->orderBy('order_items.vat_rate', 'asc')
            ->get();

        // 4. Get the 10 most recent synced orders
        $recentOrders = Order::with(['user', 'store'])
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'totals' => $totals,
            'payments' => $payments,
            'vatBreakdown' => $vatBreakdown,
            'recentOrders' => $recentOrders,
        ]);
    }
}