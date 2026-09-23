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
        $currencySymbol = StoreSetting::currencySymbol(); // 🚀 Supports EUR, GBP, BDT (৳)

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
        $currencySymbol = StoreSetting::currencySymbol(); // 🚀 Supports EUR, GBP, BDT (৳)

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
     * 🚀 TIMEZONE SAFE DATE PARSER: Resolves active store timezone dynamically (FR, UK, BD)
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseDateRange(Request $request): array
    {
        $timezone = StoreSetting::timezone();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfMonth()->startOfDay();

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
        // 🚀 Convert local store boundaries to exact UTC bounds for database querying
        $startUtc = $startDate->copy()->setTimezone('UTC');
        $endUtc   = $endDate->copy()->setTimezone('UTC');

        // Common order date selector (falls back to created_at if completed_at is null)
        $dateColumn = DB::raw('COALESCE(orders.completed_at, orders.created_at)');

        // 1. HT/TVA/TTC Totals (SQL NULL safe, excludes cancelled orders & negative Avoirs)
        $totals = Order::whereBetween(DB::raw('COALESCE(completed_at, created_at)'), [$startUtc, $endUtc])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('preparation_status')->orWhere('preparation_status', '!=', 'cancelled');
            })
            ->where('order_type', '!=', 'refund')
            ->selectRaw('
                COALESCE(SUM(total_incl_vat), 0) as total_ttc,
                COALESCE(SUM(subtotal_excl_vat), 0) as total_ht,
                COALESCE(SUM(vat_amount), 0) as total_tva,
                COUNT(*) as total_orders
            ')
            ->first();

        // 2. Payment Methods Breakdown
        $payments = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween($dateColumn, [$startUtc, $endUtc])
            ->where(function ($q) {
                $q->whereNull('orders.status')->orWhere('orders.status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('orders.preparation_status')->orWhere('orders.preparation_status', '!=', 'cancelled');
            })
            ->where('orders.order_type', '!=', 'refund')
            ->select('payments.method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.method')
            ->get();

        // 3. VAT Breakdown per tax bracket (5.5%, 10%, 20%)
        $vatBreakdown = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween($dateColumn, [$startUtc, $endUtc])
            ->where(function ($q) {
                $q->whereNull('orders.status')->orWhere('orders.status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('orders.preparation_status')->orWhere('orders.preparation_status', '!=', 'cancelled');
            })
            ->where('orders.order_type', '!=', 'refund')
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
            ->whereBetween($dateColumn, [$startUtc, $endUtc])
            ->where(function ($q) {
                $q->whereNull('orders.status')->orWhere('orders.status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('orders.preparation_status')->orWhere('orders.preparation_status', '!=', 'cancelled');
            })
            ->where('orders.order_type', '!=', 'refund')
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
            ->whereBetween('received_at', [$startUtc, $endUtc])
            ->orderBy('received_at', 'desc')
            ->get();

        $totalPurchasesCost = (float) $purchasesList->sum('total_cost');

        // 6. Operating Expenses
        $expensesList = Expense::where('category', '!=', 'food_cost')
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->whereBetween('paid_at', [$startUtc, $endUtc])
                    ->orWhere(function ($sub) use ($startUtc, $endUtc) {
                        $sub->whereNull('paid_at')
                            ->whereBetween('created_at', [$startUtc, $endUtc]);
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