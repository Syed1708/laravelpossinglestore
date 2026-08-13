<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Expense;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PnlReportController extends Controller
{
    public function index(Request $request)
    {
        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        // Date Range (Defaults to current month)
        $startDate = $request->input('start_date', Carbon::now('Europe/Paris')->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', Carbon::now('Europe/Paris')->endOfMonth()->toDateString());

        // 1. GROSS SALES REVENUE
        $salesQuery = Order::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ])->whereNotIn('status', ['refunded', 'cancelled']);

        $totalSalesTtc = (float) $salesQuery->sum('total_incl_vat');
        $totalSalesHt  = (float) $salesQuery->sum('subtotal_excl_vat');
        $totalVat      = (float) $salesQuery->sum('vat_amount');
        $orderCount    = $salesQuery->count();

        // 2. EXPENSES BREAKDOWN BY CATEGORY
        $expensesQuery = Expense::whereBetween('paid_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ])->with('expenseCategory');

        $foodCosts = (float) (clone $expensesQuery)->whereHas('expenseCategory', fn($q) => $q->where('code', 'food_cost'))->sum('amount');
        $wasteLoss = (float) (clone $expensesQuery)->whereHas('expenseCategory', fn($q) => $q->where('code', 'waste_loss'))->sum('amount');
        $salaries  = (float) (clone $expensesQuery)->whereHas('expenseCategory', fn($q) => $q->where('code', 'salaries'))->sum('amount');
        
        $otherOperatingExpenses = (float) (clone $expensesQuery)->whereHas('expenseCategory', fn($q) => $q->whereNotIn('code', ['food_cost', 'waste_loss', 'salaries']))->sum('amount');

        $totalExpenses = $foodCosts + $wasteLoss + $salaries + $otherOperatingExpenses;

        // 3. NET OPERATING PROFIT
        $netProfit = $totalSalesHt - $totalExpenses;
        $profitMargin = $totalSalesHt > 0 ? round(($netProfit / $totalSalesHt) * 100, 1) : 0;

        return view('admin.reports.pnl', compact(
            'startDate',
            'endDate',
            'currencySymbol',
            'totalSalesTtc',
            'totalSalesHt',
            'totalVat',
            'orderCount',
            'foodCosts',
            'wasteLoss',
            'salaries',
            'otherOperatingExpenses',
            'totalExpenses',
            'netProfit',
            'profitMargin'
        ));
    }
}