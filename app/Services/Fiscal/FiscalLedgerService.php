<?php

namespace App\Services\Fiscal;

use App\Models\Order;
use App\Models\DailyClosure;
use App\Mail\DailyZReportMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class FiscalLedgerService
{
    public const INITIAL_PREVIOUS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    // ==========================================
    // 🧾 1. INDIVIDUAL TICKET SIGNATURE (NF525)
    // ==========================================

    /**
     * Standardized payload string builder.
     * Normalizes zero values to eliminate negative zero ("-0.00") discrepancies.
     */
    public static function buildPayloadString(
        int $sequenceNumber,
        float $subtotalExclVat,
        float $vatAmount,
        float $totalInclVat,
        Carbon $completedAt,
        string $previousHash
    ): string {
        // Normalize any value between -0.0001 and 0.0001 to absolute positive 0.00
        $subtotal = abs($subtotalExclVat) < 0.001 ? 0.00 : $subtotalExclVat;
        $vat      = abs($vatAmount) < 0.001 ? 0.00 : $vatAmount;
        $total    = abs($totalInclVat) < 0.001 ? 0.00 : $totalInclVat;

        return "{$sequenceNumber}|"
            . number_format($subtotal, 2, '.', '') . '|'
            . number_format($vat, 2, '.', '') . '|'
            . number_format($total, 2, '.', '') . '|'
            . $completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z') . '|'
            . $previousHash;
    }

    /**
     * Standardized closure payload string builder.
     */
    public static function buildClosurePayloadString(
        int $zNumber,
        float $totalHt,
        float $totalTva,
        float $totalTtc,
        Carbon $closedAt,
        string $previousHash
    ): string {
        $cleanHt  = abs($totalHt) < 0.001 ? 0.00 : $totalHt;
        $cleanTva = abs($totalTva) < 0.001 ? 0.00 : $totalTva;
        $cleanTtc = abs($totalTtc) < 0.001 ? 0.00 : $totalTtc;

        return "{$zNumber}|"
            . number_format($cleanHt, 2, '.', '') . '|'
            . number_format($cleanTva, 2, '.', '') . '|'
            . number_format($cleanTtc, 2, '.', '') . '|'
            . $closedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z') . '|'
            . $previousHash;
    }

    /**
     * Generates previous hash and the new SHA-256 cryptographic hash for an individual order.
     *
     * @return array{previous_hash: string, hash: string, completed_at: Carbon}
     */
    public function generateSignature(
        int $sequenceNumber,
        float $subtotalExclVat,
        float $vatAmount,
        float $totalInclVat,
        ?Carbon $completedAt = null
    ): array {
        $completedAt = $completedAt ? (clone $completedAt)->setTimezone('UTC') : Carbon::now('UTC');

        // Lock previous valid hashed order to guarantee linear chaining
        $lastHashOrder = Order::whereNotNull('hash')
            ->where('hash', '!=', '')
            ->orderBy('sequence_number', 'desc')
            ->lockForUpdate()
            ->first();

        $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash))
            ? $lastHashOrder->hash
            : self::INITIAL_PREVIOUS_HASH;

        // NF525 Monotonic Time Rule: Never allow timestamp to move backwards in the chain
        if ($lastHashOrder && $lastHashOrder->completed_at) {
            $lastOrderTime = $lastHashOrder->completed_at->setTimezone('UTC');
            if ($completedAt->lt($lastOrderTime)) {
                // If offline order is older than the last chained order, anchor it at current UTC time
                $completedAt = Carbon::now('UTC');
            }
        }

        $dataToHash = self::buildPayloadString(
            $sequenceNumber,
            $subtotalExclVat,
            $vatAmount,
            $totalInclVat,
            $completedAt,
            $previousHash
        );

        $hash = hash('sha256', $dataToHash);

        return [
            'previous_hash' => $previousHash,
            'hash'          => $hash,
            'completed_at'  => $completedAt,
        ];
    }

    // ==========================================
    // 📊 2. DAILY Z-CLOSURE OPERATIONS (NF525)
    // ==========================================

    /**
     * Get live shift financial summary for modal preview before closing
     *
     * @return array<string, mixed>
     */
    public function getShiftSummary(): array
    {
        $openOrders = Order::whereNull('daily_closure_id')->get();

        $totalTtc = (float) $openOrders->sum('total_incl_vat');
        $totalHt  = (float) $openOrders->sum('subtotal_excl_vat');
        $totalTva = (float) $openOrders->sum('vat_amount');

        $orderIds = $openOrders->pluck('id')->toArray();

        $payments = DB::table('payments')
            ->whereIn('order_id', $orderIds)
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->pluck('total', 'method')
            ->toArray();

        return [
            'open_orders_count'  => $openOrders->count(),
            'total_ttc'          => round($totalTtc, 2),
            'total_ht'           => round($totalHt, 2),
            'total_tva'          => round($totalTva, 2),
            'cash_sales'         => round((float) ($payments['cash'] ?? 0), 2),
            'card_sales'         => round((float) ($payments['card'] ?? 0), 2),
            'payments_breakdown' => $payments,
        ];
    }

    /**
     * Atomically process and generate an immutable NF525 Daily Z-Report & email manager
     */
    public function processDailyClosure(): DailyClosure
    {
        return DB::transaction(function () {
            // 1. Lock unclosed orders to prevent modifications during calculation
            $openOrders = Order::whereNull('daily_closure_id')
                ->lockForUpdate()
                ->get();

            if ($openOrders->isEmpty()) {
                throw new RuntimeException('No open orders available to close for today.');
            }

            $orderIds = $openOrders->pluck('id')->toArray();

            // 2. Lock previous Z-Report to guarantee unbroken hash chain
            $lastClosure = DailyClosure::orderBy('z_number', 'desc')
                ->lockForUpdate()
                ->first();

            $previousHash = $lastClosure?->hash ?? self::INITIAL_PREVIOUS_HASH;
            $nextZNumber  = $lastClosure ? ($lastClosure->z_number + 1) : 1;

            // 3. Consolidated Financials
            $totalTtc = round((float) $openOrders->sum('total_incl_vat'), 2);
            $totalHt  = round((float) $openOrders->sum('subtotal_excl_vat'), 2);
            $totalTva = round((float) $openOrders->sum('vat_amount'), 2);

            // 4. Payment Method Breakdown
            $payments = DB::table('payments')
                ->whereIn('order_id', $orderIds)
                ->select('method', DB::raw('SUM(amount) as total'))
                ->groupBy('method')
                ->pluck('total', 'method')
                ->map(fn($amt) => round((float) $amt, 2))
                ->toArray();

            // 5. Dynamic VAT Breakdown per French tax bracket (5.5%, 10%, 20%)
            $vatBreakdown = DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->select(
                    'vat_rate',
                    DB::raw('SUM(subtotal) as total_ttc'),
                    DB::raw('SUM(subtotal - (subtotal / (1 + (vat_rate / 100)))) as collected_vat')
                )
                ->groupBy('vat_rate')
                ->get()
                ->mapWithKeys(fn ($item) => [
                    (string) $item->vat_rate => [
                        'ttc' => round((float) $item->total_ttc, 2),
                        'vat' => round((float) $item->collected_vat, 2),
                    ]
                ])
                ->toArray();

            // Reconcile VAT breakdown sum against order total TVA to prevent €0.01 rounding drift
            $breakdownVatSum = array_sum(array_column($vatBreakdown, 'vat'));
            $vatDiff = round($totalTva - $breakdownVatSum, 2);
            if ($vatDiff != 0 && !empty($vatBreakdown)) {
                // Adjust largest bucket by the 1-cent discrepancy so the audit ledger balances perfectly
                $firstKey = array_key_first($vatBreakdown);
                $vatBreakdown[$firstKey]['vat'] = round($vatBreakdown[$firstKey]['vat'] + $vatDiff, 2);
            }

            $closedAt = Carbon::now('UTC');

            // 6. Generate SHA-256 Daily Closure Signature using unified builder
            $dataToHash = self::buildClosurePayloadString(
                $nextZNumber,
                $totalHt,
                $totalTva,
                $totalTtc,
                $closedAt,
                $previousHash
            );

            $currentHash = hash('sha256', $dataToHash);

            // 7. Save Daily Z-Report
            $closure = DailyClosure::create([
                'z_number'           => $nextZNumber,
                'total_ttc'          => $totalTtc,
                'total_ht'           => $totalHt,
                'total_tva'          => $totalTva,
                'vat_breakdown'      => $vatBreakdown,
                'payments_breakdown' => $payments,
                'hash'               => $currentHash,
                'previous_hash'      => $previousHash,
                'closed_at'          => $closedAt,
            ]);

            // 8. Freeze open orders permanently
            Order::whereIn('id', $orderIds)->update(['daily_closure_id' => $closure->id]);

            // 9. Dispatch Automated Manager Email & PDF Attachment
            try {
                $managerEmail = config('mail.from.address') ?? 'manager@burgerpalace.fr';
                Mail::to($managerEmail)->send(new DailyZReportMail($closure));
            } catch (Throwable $e) {
                Log::warning('Failed to dispatch Z-Report email: ' . $e->getMessage());
            }

            return $closure;
        });
    }
}