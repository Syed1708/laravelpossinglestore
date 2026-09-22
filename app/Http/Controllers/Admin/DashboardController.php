<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StoreHoursHelper;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\StoreSetting;

class DashboardController extends Controller
{
    public function index()
    {
        $today = StoreHoursHelper::today();
        $startOfMonth = StoreHoursHelper::now()->startOfMonth();
        $endOfMonth = StoreHoursHelper::now()->endOfMonth();

        $settings = StoreSetting::getSettings();
        $currencySymbol = StoreSetting::currencySymbol();

        // 1. TODAY'S SALES & ORDERS
        $todayOrders = Order::whereDate('created_at', $today)
            ->whereNotIn('status', ['refunded', 'cancelled'])
            ->get();

        $todayRevenue = (float) $todayOrders->sum('total_incl_vat');
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
        $monthSalesHt = (float) Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['refunded', 'cancelled'])
            ->sum('subtotal_excl_vat');

        $monthExpenses = (float) Expense::whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthNetProfit = $monthSalesHt - $monthExpenses;

        // 5. 📊 7-DAY REVENUE TRAJECTORY
        $trendLabels = [];
        $trendData   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = (clone $today)->subDays($i);
            $dayRevenue = Order::whereDate('created_at', $date)
                ->whereNotIn('status', ['refunded', 'cancelled'])
                ->sum('total_incl_vat');

            $trendLabels[] = $date->format('D, d M'); // e.g. "Mon, 21 Sep"
            $trendData[]   = round((float) $dayRevenue, 2);
        }

        // 6. 📊 SALES CHANNELS DISTRIBUTION
        $channelLabels = ['Dine-In', 'Takeaway', 'Online'];
        $channelData   = [$dineInCount, $takeawayCount, $onlineCount];

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
            'monthNetProfit',
            'trendLabels',
            'trendData',
            'channelLabels',
            'channelData'
        ));
    }
}