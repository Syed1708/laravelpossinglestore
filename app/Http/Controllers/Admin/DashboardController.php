<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Ingredient;
use App\Models\Expense;
use App\Models\StoreSetting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today('Europe/Paris');
        $startOfMonth = Carbon::now('Europe/Paris')->startOfMonth();
        $endOfMonth = Carbon::now('Europe/Paris')->endOfMonth();

        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        // 1. TODAY'S SALES & ORDERS
        $todayOrders = Order::whereDate('created_at', $today)
            ->whereNotIn('status', ['refunded', 'cancelled'])
            ->get();

        $todayRevenue = $todayOrders->sum('total_incl_vat');
        $todayOrderCount = $todayOrders->count();

        // Sales Channel Breakdown Today
        $onlineCount   = $todayOrders->whereIn('order_type', ['click_and_collect', 'online'])->count();
        $takeawayCount = $todayOrders->where('order_type', 'takeaway')->count();
        $dineInCount   = $todayOrders->where('order_type', 'dine_in')->count();

        // 2. LIVE DISPATCHER & RESERVATIONS QUEUE
        $pendingOnlineOrders = Order::where('preparation_status', 'not_accepted')
            ->whereIn('order_type', ['click_and_collect', 'online'])
            ->with('items')
            ->latest()
            ->take(5)
            ->get();

        $todayReservations = Reservation::whereDate('reservation_date', $today)
            ->whereIn('status', ['confirmed', 'seated'])
            ->with('table')
            ->orderBy('reservation_time', 'asc')
            ->take(5)
            ->get();

        // 3. LOW INVENTORY STOCK ALERTS
        $lowStockIngredients = Ingredient::whereColumn('stock_level', '<=', 'alert_level')
            ->orderBy('stock_level', 'asc')
            ->get();

        // 4. MONTHLY FINANCIAL P&L SUMMARY
        $monthSalesHt = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['refunded', 'cancelled'])
            ->sum('subtotal_excl_vat');

        $monthExpenses = Expense::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthNetProfit = $monthSalesHt - $monthExpenses;

        return view('admin.dashboard', compact(
            'currencySymbol',
            'settings',
            'todayRevenue',
            'todayOrderCount',
            'onlineCount',
            'takeawayCount',
            'dineInCount',
            'pendingOnlineOrders',
            'todayReservations',
            'lowStockIngredients',
            'monthSalesHt',
            'monthExpenses',
            'monthNetProfit'
        ));
    }
}