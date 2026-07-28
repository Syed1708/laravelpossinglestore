<?php

namespace App\Http\Controllers\Api\v1\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DailyClosure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DayClosureApiController extends Controller
{
    /**
     * GET /api/v1/pos/z-closure/summary
     * Live shift summary for the Z-Closure modal preview
     */
    public function getShiftSummary(Request $request)
    {
        $openOrders = Order::whereNull('daily_closure_id')->get();

        $totalTtc = $openOrders->sum('total_incl_vat');
        $totalHt = $openOrders->sum('subtotal_excl_vat');
        $totalTva = $openOrders->sum('vat_amount');

        $orderIds = $openOrders->pluck('id');

        $payments = DB::table('payments')
            ->whereIn('order_id', $orderIds)
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->pluck('total', 'method')
            ->toArray();

        return response()->json([
            'open_orders_count' => $openOrders->count(),
            'total_ttc' => round($totalTtc, 2),
            'total_ht' => round($totalHt, 2),
            'total_tva' => round($totalTva, 2),
            'cash_sales' => round($payments['cash'] ?? 0, 2),
            'card_sales' => round($payments['card'] ?? 0, 2),
            'payments_breakdown' => $payments,
        ], 200);
    }

    /**
     * POST /api/v1/pos/z-closure/confirm
     * Generate Daily Z-Report, compute SHA-256 hash, and freeze open orders
     */
    public function closeDay(Request $request)
    {
        // 1. Fetch all unclosed orders
        $openOrders = Order::whereNull('daily_closure_id')->get();

        if ($openOrders->isEmpty()) {
            return response()->json([
                'message' => 'Aucune commande ouverte à clôturer pour aujourd\'hui.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // 2. Fetch the previous Z-Report hash to maintain chain
            $lastClosure = DailyClosure::orderBy('z_number', 'desc')->first();
            $previousHash = $lastClosure ? $lastClosure->hash : '0000000000000000000000000000000000000000000000000000000000000000';
            $nextZNumber = $lastClosure ? ($lastClosure->z_number + 1) : 1;

            // 3. Consolidated Financials
            $totalTtc = $openOrders->sum('total_incl_vat');
            $totalHt = $openOrders->sum('subtotal_excl_vat');
            $totalTva = $openOrders->sum('vat_amount');

            // 4. Payment Method Breakdown
            $orderIds = $openOrders->pluck('id');
            $payments = DB::table('payments')
                ->whereIn('order_id', $orderIds)
                ->select('method', DB::raw('SUM(amount) as total'))
                ->groupBy('method')
                ->get()
                ->pluck('total', 'method')
                ->toArray();

            // 5. VAT Breakdown (5.5%, 10%, 20%)
            $vatBreakdown = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->select(
                    'vat_rate',
                    DB::raw('SUM(subtotal) as total_ttc'),
                    DB::raw('SUM(subtotal - (subtotal / (1 + (vat_rate / 100)))) as collected_vat')
                )
                ->groupBy('vat_rate')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [
                        (string)$item->vat_rate => [
                            'ttc' => round($item->total_ttc, 2),
                            'vat' => round($item->collected_vat, 2)
                        ]
                    ];
                })
                ->toArray();

            $closedAt = Carbon::now();

            // 6. Generate secure SHA-256 Daily Closure Signature
            $dataToHash = "{$nextZNumber}|" . number_format($totalHt, 2, '.', '') . "|" . number_format($totalTva, 2, '.', '') . "|" . number_format($totalTtc, 2, '.', '') . "|{$closedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            
            $currentHash = hash('sha256', $dataToHash);

            // 7. Save Daily Z-Report Record
            $closure = DailyClosure::create([
                'z_number' => $nextZNumber,
                'total_ttc' => $totalTtc,
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'vat_breakdown' => $vatBreakdown,
                'payments_breakdown' => $payments,
                'hash' => $currentHash,
                'previous_hash' => $previousHash,
                'closed_at' => $closedAt,
            ]);

            // 8. 🛡️ FREEZE ORDERS: Link unclosed orders to this Z-Report
            Order::whereIn('id', $orderIds)->update(['daily_closure_id' => $closure->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Z-Report #{$nextZNumber} généré avec succès !",
                'closure' => [
                    'id' => $closure->id,
                    'z_number' => $closure->z_number,
                    'total_ttc' => round($closure->total_ttc, 2),
                    'total_ht' => round($closure->total_ht, 2),
                    'total_tva' => round($closure->total_tva, 2),
                    'hash' => $closure->hash,
                    'closed_at' => $closure->closed_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Échec de la clôture : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/pos/z-closure/history
     * Fetch past Daily Z-Reports for the POS Z-Closure Archive tab
     */
    public function getClosureHistory(Request $request)
    {
        $closures = DailyClosure::orderBy('z_number', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $closures->count(),
            'data' => $closures,
        ], 200);
    }
}