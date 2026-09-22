<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StoreHoursHelper;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = StoreSetting::currencySymbol();
        $tz = StoreSetting::timezone();
        $now = StoreHoursHelper::now();

        // 🚀 1. PERIOD RESOLUTION (Default: 'month')
        $period = $request->input('period', 'month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate   = $now->copy()->endOfDay();
                $periodLabel = 'Today (' . $now->format('d M') . ')';
                break;

            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate   = $now->copy()->endOfWeek();
                $periodLabel = 'This Week (' . $startDate->format('d M') . ' - ' . $endDate->format('d M') . ')';
                break;

            case 'custom':
                $startDate = $customStart ? Carbon::parse($customStart, $tz)->startOfDay() : $now->copy()->startOfMonth();
                $endDate   = $customEnd ? Carbon::parse($customEnd, $tz)->endOfDay() : $now->copy()->endOfDay();
                $periodLabel = $startDate->format('d M') . ' - ' . $endDate->format('d M Y');
                break;

            case 'month':
            default:
                $period = 'month';
                $startDate = $now->copy()->startOfMonth();
                $endDate   = $now->copy()->endOfMonth();
                $periodLabel = 'This Month (' . $now->format('F Y') . ')';
                break;
        }

        // Convert selected boundaries to UTC for database querying
        $startUtc = $startDate->copy()->setTimezone('UTC');
        $endUtc   = $endDate->copy()->setTimezone('UTC');

        // 🚀 2. FETCH ORDERS IN THE SELECTED PERIOD
        $periodOrders = Order::whereBetween('created_at', [$startUtc, $endUtc])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('preparation_status')->orWhere('preparation_status', '!=', 'cancelled');
            })
            ->where('order_type', '!=', 'refund')
            ->get();

        // Financial KPIs
        $revenue = (float) $periodOrders->sum('total_incl_vat');
        $orderCount = $periodOrders->count();

        // Channel Breakdown for Period
        $onlineCount   = $periodOrders->whereIn('order_type', ['click_and_collect', 'online'])->count();
        $takeawayCount = $periodOrders->where('order_type', 'takeaway')->count();
        $dineInCount   = $periodOrders->where('order_type', 'dine_in')->count();

        // P&L Net Profit for Period
        $salesHt = (float) $periodOrders->sum('subtotal_excl_vat');
        $expenses = (float) Expense::whereBetween('paid_at', [$startUtc, $endUtc])->sum('amount');
        $netProfit = $salesHt - $expenses;

        // 🚀 3. DYNAMIC REVENUE TREND CHART DATA (Calculated in-memory from collection)
        $trendLabels = [];
        $trendData   = [];

        if ($period === 'today') {
            // Group by 2-hour intervals for Today
            for ($h = 8; $h <= 22; $h += 2) {
                $hStart = $startDate->copy()->hour($h)->minute(0)->second(0)->setTimezone('UTC');
                $hEnd   = $startDate->copy()->hour($h + 1)->minute(59)->second(59)->setTimezone('UTC');

                $slotSum = $periodOrders->filter(fn ($o) => $o->created_at >= $hStart && $o->created_at <= $hEnd)->sum('total_incl_vat');
                $trendLabels[] = sprintf('%02d:00', $h);
                $trendData[]   = round((float) $slotSum, 2);
            }
        } elseif ($period === 'week') {
            // Group by the 7 days of this week
            for ($d = 0; $d < 7; $d++) {
                $day = $startDate->copy()->addDays($d);
                $dStart = $day->copy()->startOfDay()->setTimezone('UTC');
                $dEnd   = $day->copy()->endOfDay()->setTimezone('UTC');

                $daySum = $periodOrders->filter(fn ($o) => $o->created_at >= $dStart && $o->created_at <= $dEnd)->sum('total_incl_vat');
                $trendLabels[] = $day->format('D, d M');
                $trendData[]   = round((float) $daySum, 2);
            }
        } else {
            // Month / Custom: Group by daily points
            $totalDays = $startDate->diffInDays($endDate) + 1;
            $step = max(1, (int) ceil($totalDays / 15)); // Sample days if range is wide

            for ($d = 0; $d < $totalDays; $d += $step) {
                $day = $startDate->copy()->addDays($d);
                if ($day->gt($endDate)) break;

                $dStart = $day->copy()->startOfDay()->setTimezone('UTC');
                $dEnd   = ($step > 1) ? $day->copy()->addDays($step - 1)->endOfDay()->setTimezone('UTC') : $day->copy()->endOfDay()->setTimezone('UTC');

                $sum = $periodOrders->filter(fn ($o) => $o->created_at >= $dStart && $o->created_at <= $dEnd)->sum('total_incl_vat');
                $trendLabels[] = $day->format('d M');
                $trendData[]   = round((float) $sum, 2);
            }
        }

        // 🚀 4. DONUT CHART DATA
        $channelLabels = ['Dine-In', 'Takeaway', 'Online'];
        $channelData   = [$dineInCount, $takeawayCount, $onlineCount];

        // 🚀 5. OPERATIONS QUEUES
        $pendingOnlineOrders = Order::where('preparation_status', 'not_accepted')
            ->whereIn('order_type', ['click_and_collect', 'online'])
            ->with(['items', 'client'])
            ->latest()
            ->take(5)
            ->get();

        $todayReservations = Reservation::whereDate('reservation_date', $now->toDateString())
            ->whereIn('status', ['confirmed', 'seated'])
            ->with('table')
            ->orderBy('reservation_time', 'asc')
            ->take(5)
            ->get();

        $lowStockIngredients = Ingredient::whereColumn('stock_level', '<=', 'alert_level')
            ->orderBy('stock_level', 'asc')
            ->get();

        return view('admin.dashboard', compact(
            'currencySymbol',
            'settings',
            'period',
            'periodLabel',
            'revenue',
            'orderCount',
            'onlineCount',
            'takeawayCount',
            'dineInCount',
            'salesHt',
            'expenses',
            'netProfit',
            'trendLabels',
            'trendData',
            'channelLabels',
            'channelData',
            'pendingOnlineOrders',
            'todayReservations',
            'lowStockIngredients',
            'customStart',
            'customEnd'
        ));
    }
}