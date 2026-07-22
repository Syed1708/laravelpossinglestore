<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the reporting page with filtered totals.
     */
    public function index(Request $request)
    {
        // 1. Parse date ranges (Defaults to current month if empty)
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        // 2. Aggregate Data
        $data = $this->calculateReportData($startDate, $endDate);

        // Tyro admin role check
        $user = auth()->user();
        $isAdmin = $user->hasRole('super-admin') || $user->hasRole('admin');

        return view('admin.reports.index', array_merge($data, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'isAdmin' => $isAdmin
        ]));
    }


     /**
     * Generate and stream a clean, specialized PDF report.
     */
    public function downloadPdf(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        
        // 🚀 Get the requested report type (sales, purchases, expenses, p_and_l)
        $reportType = $request->input('report_type', 'p_and_l');

        $data = $this->calculateReportData($startDate, $endDate);

        // Compile the PDF using our unified template
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', array_merge($data, [
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'reportType' => $reportType
        ]));

        return $pdf->download("rapport-{$reportType}-{$startDate->format('Ymd')}-{$endDate->format('Ymd')}.pdf");
    }

    /**
     * Helper to perform complex accounting queries for a date range.
     */
    private function calculateReportData($startDate, $endDate)
    {
        // 1. HT/TVA/TTC Totals
        $totals = Order::whereBetween('completed_at', [$startDate, $endDate])
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
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->select('payments.method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.method')
            ->get();

        // 3. VAT Breakdown per French tax bracket
        $vatBreakdown = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->select(
                'order_items.vat_rate',
                DB::raw('SUM(order_items.subtotal) as total_ttc'),
                DB::raw('SUM(order_items.subtotal - (order_items.subtotal / (1 + (order_items.vat_rate / 100)))) as collected_vat')
            )
            ->groupBy('order_items.vat_rate')
            ->orderBy('order_items.vat_rate', 'asc')
            ->get();

        // 🚀 4. NEW: Top-Selling Products (Volume analysis for Sales Report)
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as qty_sold'), DB::raw('SUM(order_items.subtotal) as total_ttc'))
            ->groupBy('order_items.product_name')
            ->orderBy('qty_sold', 'desc')
            ->get();

        // 🚀 5. NEW: Received Purchase Orders (For Purchases Report)
        $purchasesList = PurchaseOrder::with(['supplier', 'items.ingredient'])
            ->where('status', 'received')
            ->whereBetween('received_at', [$startDate, $endDate])
            ->get();

        $totalPurchasesCost = $purchasesList->sum('total_cost');

        // 🚀 6. NEW: Operating Expenses (For Expenses Report)
        $expensesList = Expense::where('category', '!=', 'food_cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalExpensesCost = $expensesList->sum('amount');

        return [
            'totals' => $totals,
            'payments' => $payments,
            'vatBreakdown' => $vatBreakdown,
            'topProducts' => $topProducts,
            'purchasesList' => $purchasesList,
            'totalPurchasesCost' => $totalPurchasesCost,
            'expensesList' => $expensesList,
            'totalExpensesCost' => $totalExpensesCost,
        ];
    }
}