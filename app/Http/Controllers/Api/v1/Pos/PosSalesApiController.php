<?php

namespace App\Http\Controllers\Api\v1\Pos;

use App\Events\KdsOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Fiscal\FiscalLedgerService;
use App\Services\Inventory\StockService;
use App\Services\Orders\SequenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PosSalesApiController extends Controller
{
    public function __construct(
        protected SequenceService $sequenceService,
        protected FiscalLedgerService $fiscalService,
        protected StockService $stockService
    ) {}

    /**
     * GET /api/v1/pos/sales
     */
    public function getSalesHistory(Request $request): JsonResponse
    {
        $orders = Order::with(['items', 'payments', 'client'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $orders->count(),
            'data'    => $orders,
        ], 200);
    }

    /**
     * GET /api/v1/pos/sales/{id}
     */
    public function showOrderDetails(Request $request, $id): JsonResponse
    {
        $order = Order::with(['items', 'payments', 'client'])
            ->where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'order'   => $order,
        ], 200);
    }

    /**
     * POST /api/v1/pos/refund/{id}
     * Supports finding order by integer ID or string UUID.
     * Restores raw inventory and creates NF525 compliant Avoir (Credit Note).
     */
    public function refundOrder(Request $request, $id): JsonResponse
    {
        $originalOrder = Order::with(['items', 'payments', 'client'])
            ->where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        if ($originalOrder->status === 'refunded' || $originalOrder->preparation_status === 'cancelled') {
            return response()->json([
                'message' => 'This order has already been refunded or cancelled.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Mark original order as refunded and cancelled
            $originalOrder->update([
                'status'             => 'refunded',
                'preparation_status' => 'cancelled', // 👈 Drops ticket from kitchen KDS live!
            ]);

            // 2. 🚀 RESTORE INVENTORY STOCK (Fixed: was previously missing!)
            $this->stockService->restoreStockForOrder($originalOrder);

            // 3. Sequential numbering for credit note
            $sequenceNumber = $this->sequenceService->getNextSequenceNumber();

            // 4. Calculate negative totals for Avoir
            $subtotalExclVat = -abs((float) $originalOrder->subtotal_excl_vat);
            $vatAmount       = -abs((float) $originalOrder->vat_amount);
            $totalInclVat    = -abs((float) $originalOrder->total_incl_vat);
            $completedAt     = Carbon::now('UTC');

            // 5. 🚀 Generate NF525 Cryptographic Signature via centralized service (Prevents -0.00 bug)
            $signature = $this->fiscalService->generateSignature(
                $sequenceNumber,
                $subtotalExclVat,
                $vatAmount,
                $totalInclVat,
                $completedAt
            );

            // 6. Create Avoir Order
            $refundOrder = Order::create([
                'uuid'               => (string) Str::uuid(),
                'user_id'            => auth('sanctum')->id() ?? $originalOrder->user_id,
                'client_id'          => $originalOrder->client_id,
                'customer_name'      => "Refund (#{$originalOrder->sequence_number})",
                'customer_phone'     => $originalOrder->customer_phone,
                'order_type'         => 'refund',
                'sequence_number'    => $sequenceNumber,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                'vat_amount'         => round($vatAmount, 2),
                'total_incl_vat'     => round($totalInclVat, 2),
                'hash'               => $signature['hash'],
                'previous_hash'      => $signature['previous_hash'],
                'completed_at'       => $signature['completed_at'],
                'preparation_status' => 'cancelled', // Exclude Avoir from KDS
                'status'             => 'completed',
            ]);

            foreach ($originalOrder->items as $item) {
                $refundOrder->items()->create([
                    'product_id'   => $item->product_id,
                    'product_name' => "Refund: {$item->product_name}",
                    'quantity'     => -abs((int) $item->quantity),
                    'unit_price'   => (float) $item->unit_price,
                    'vat_rate'     => (float) $item->vat_rate,
                    'subtotal'     => -abs((float) $item->subtotal),
                ]);
            }

            foreach ($originalOrder->payments as $payment) {
                $refundOrder->payments()->create([
                    'amount' => -abs((float) $payment->amount),
                    'method' => $payment->method,
                ]);
            }

            // 7. Deduct back awarded loyalty points if customer account attached
            if (!empty($originalOrder->client_id) && $originalOrder->points_earned > 0) {
                $client = $originalOrder->client;
                if ($client) {
                    $client->decrement('loyalty_points', $originalOrder->points_earned);
                }
            }

            DB::commit();

            // 8. Broadcast real-time WebSocket event to kitchen screens
            try {
                event(new KdsOrderUpdated('order_refunded', $originalOrder));
            } catch (Throwable $e) {}

            return response()->json([
                'success'      => true,
                'message'      => "Refund #{$sequenceNumber} created for order #{$originalOrder->sequence_number}!",
                'refund_order' => $refundOrder,
            ], 200);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Refund failed: ' . $e->getMessage()], 500);
        }
    }
}