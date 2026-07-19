<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DailyClosure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyClosureController extends Controller
{
    /**
     * Process and generate a legally compliant Daily Z-Report.
     */
    public function closeDay(Request $request)
    {
        // 1. Fetch all orders that have not been closed (archived) yet
        $openOrders = Order::whereNull('daily_closure_id')->get();

        if ($openOrders->isEmpty()) {
            return redirect()->back()->with('error', 'Aucune commande ouverte à clôturer pour aujourd\'hui.');
        }

        DB::beginTransaction();
        try {
            // 2. Fetch the previous Z-Report hash to maintain the chain
            $lastClosure = DailyClosure::orderBy('z_number', 'desc')->first();
            $previousHash = $lastClosure ? $lastClosure->hash : '0000000000000000000000000000000000000000000000000000000000000000';
            $nextZNumber = $lastClosure ? ($lastClosure->z_number + 1) : 1;

            // 3. Calculate Consolidated Financials
            $totalTtc = $openOrders->sum('total_incl_vat');
            $totalHt = $openOrders->sum('subtotal_excl_vat');
            $totalTva = $openOrders->sum('vat_amount');

            // 4. Calculate Payment Method Breakdown
            $orderIds = $openOrders->pluck('id');
            $payments = DB::table('payments')
                ->whereIn('order_id', $orderIds)
                ->select('method', DB::raw('SUM(amount) as total'))
                ->groupBy('method')
                ->get()
                ->pluck('total', 'method')
                ->toArray();

            // 5. Calculate VAT Breakdown per French bracket (5.5%, 10%, 20%)
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
             // 🚀 THE FIX: Convert to UTC and format without milliseconds to match our order standard
            $dataToHash = "{$nextZNumber}|" . number_format($totalHt, 2, '.', '') . "|" . number_format($totalTva, 2, '.', '') . "|" . number_format($totalTtc, 2, '.', '') . "|{$closedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            
            $currentHash = hash('sha256', $dataToHash);

            // 7. Save the Daily Z-Report
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

            // 8. 🛡️ FREEZE ORDERS: Link today's orders to the new Z-Report,
            // locking them permanently from any future database alterations!
            Order::whereIn('id', $orderIds)->update(['daily_closure_id' => $closure->id]);

            DB::commit();
            return redirect()->back()->with('success', "Z-Report #{$nextZNumber} généré avec succès ! Toutes les commandes ont été clôturées et archivées.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Échec de la clôture : ' . $e->getMessage());
        }
    }
}