<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $isAdmin = $user->hasRole('superadmin') || $user->hasRole('admin');

        return view('admin.reports.index', array_merge($data, [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'isAdmin' => $isAdmin
        ]));
    }

    /**
     * Generate and stream a clean, downloadable PDF report.
     */
    public function downloadPdf(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        $data = $this->calculateReportData($startDate, $endDate);

        // 🚀 THE MAGIC: Load a clean print-ready blade view, and convert to PDF!
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.pdf', array_merge($data, [
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
        ]));

        // Downloads the file instantly as "rapport-ventes-bordeaux.pdf"
        return $pdf->download("rapport-ventes-{$startDate->format('Ymd')}-{$endDate->format('Ymd')}.pdf");
    }

    /**
     * Helper to perform complex accounting queries for a date range.
     */
    private function calculateReportData($startDate, $endDate)
    {
        // HT/TVA/TTC Totals
        $totals = Order::whereBetween('completed_at', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(total_incl_vat), 0) as total_ttc,
                COALESCE(SUM(subtotal_excl_vat), 0) as total_ht,
                COALESCE(SUM(vat_amount), 0) as total_tva,
                COUNT(*) as total_orders
            ')
            ->first();

        // Payment Methods Breakdown
        $payments = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->select('payments.method', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('payments.method')
            ->get();

        // VAT Collected per French tax bracket
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

        return [
            'totals' => $totals,
            'payments' => $payments,
            'vatBreakdown' => $vatBreakdown,
        ];
    }
}