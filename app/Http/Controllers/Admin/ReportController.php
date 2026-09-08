<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reporting page with filtered totals.
     */
    public function index(Request $request): View
    {
        [$startDate, $endDate] = $this->parseDateRange($request);
        $data = $this->calculateReportData($startDate, $endDate);

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('admin'));

        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        return view('admin.reports.index', array_merge($data, [
            'startDate'      => $startDate->format('Y-m-d'),
            'endDate'        => $endDate->format('Y-m-d'),
            'isAdmin'        => $isAdmin,
            'settings'       => $settings,
            'currencySymbol' => $currencySymbol,
        ]));
    }

    /**
     * Generate and stream a specialized PDF report.
     */
    public function downloadPdf(Request $request): Response
    {
        [$startDate, $endDate] = $this->parseDateRange($request);
        $reportType = $request->input('report_type', 'p_and_l');
        $data = $this->calculateReportData($startDate, $endDate);

        $settings = StoreSetting::getSettings();
        $currencySymbol = $settings->currency === 'GBP' ? '£' : '€';

        $pdf = Pdf::loadView('admin.reports.pdf', array_merge($data, [
            'startDate'      => $startDate->format('d/m/Y'),
            'endDate'        => $endDate->format('d/m/Y'),
            'reportType'     => $reportType,
            'settings'       => $settings,
            'currencySymbol' => $currencySymbol,
        ]));

        $filename = "report-{$reportType}-{$startDate->format('Ymd')}-{$endDate->format('Ymd')}.pdf";

        return $pdf->download($filename);
    }

    /**
     * 🚀 TIMEZONE SAFE DATE PARSER: Standardizes Europe/Paris date bounds
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseDateRange(Request $request): array
    {
        $timezone = 'Europe/Paris';

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'), $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * Helper to perform accounting aggregations for a date range.
     *
     * @return array<string, mixed>
     */
    private function calculateReportData(Carbon $startDate, Carbon $endDate): array
    {
        // 1. HT/TVA/TTC Totals (🚀 Strictly excludes cancelled orders)
        $totals = Order::whereBetween('completed_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->where('preparation_status', '!=', 'cancelled')
            ->selectRaw('
                COALESCE(SUM(total_incl_vat), 0) as total_ttc,
                COALESCE(SUM(subtotal_excl_vat), 0) as total_ht,
                COALESCE(SUM(vat_amount), 0) as total_tva,
                COUNT(*) as total_orders
            ')
            ->first();

        // 2. Payment Methods Breakdown (Excludes cancelled orders)
        $payments = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('orders.preparation_status', '!=', 'cancelled')
            ->select('payments.method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.method')
            ->get();

        // 3. VAT Breakdown per French tax bracket (5.5%, 10%, 20%)
        $vatBreakdown = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('orders.preparation_status', '!=', 'cancelled')
            ->select(
                'order_items.vat_rate',
                DB::raw('SUM(order_items.subtotal) as total_ttc'),
                DB::raw('SUM(order_items.subtotal - (order_items.subtotal / (1 + (order_items.vat_rate / 100)))) as collected_vat')
            )
            ->groupBy('order_items.vat_rate')
            ->orderBy('order_items.vat_rate', 'asc')
            ->get();

        // 4. Top-Selling Products (Volume & Revenue analysis - Top 15)
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', ['cancelled'])
            ->where('orders.preparation_status', '!=', 'cancelled')
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.subtotal) as total_ttc')
            )
            ->groupBy('order_items.product_name')
            ->orderBy('qty_sold', 'desc')
            ->take(15)
            ->get();

        // 5. Received Purchase Orders (Supplier Deliveries)
        $purchasesList = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('status', 'received')
            ->whereBetween('received_at', [$startDate, $endDate])
            ->orderBy('received_at', 'desc')
            ->get();

        $totalPurchasesCost = (float) $purchasesList->sum('total_cost');

        // 6. Operating Expenses (🚀 Filtered by paid_at date matching P&L)
        $expensesList = Expense::where('category', '!=', 'food_cost')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('paid_at', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('paid_at')
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
            })
            ->with('expenseCategory')
            ->orderBy('paid_at', 'desc')
            ->get();

        $totalExpensesCost = (float) $expensesList->sum('amount');

        return [
            'totals'             => $totals,
            'payments'           => $payments,
            'vatBreakdown'       => $vatBreakdown,
            'topProducts'        => $topProducts,
            'purchasesList'      => $purchasesList,
            'totalPurchasesCost' => $totalPurchasesCost,
            'expensesList'       => $expensesList,
            'totalExpensesCost'  => $totalExpensesCost,
        ];
    }
}