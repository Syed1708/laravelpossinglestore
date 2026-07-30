<?php

namespace App\Http\Controllers\Api\v1\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Events\KdsOrderUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PosSalesApiController extends Controller
{
    /**
     * GET /api/v1/pos/sales
     */
    public function getSalesHistory(Request $request)
    {
        $orders = Order::with(['items', 'payments'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders,
        ], 200);
    }

    /**
     * GET /api/v1/pos/sales/{id}
     */
    public function showOrderDetails(Request $request, $id)
    {
        $order = Order::with(['items', 'payments', 'client'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'order' => $order,
        ], 200);
    }

/**
     * POST /api/v1/pos/refund/{id}
     * Supports finding order by integer ID or string UUID!
     */
    public function refundOrder(Request $request, $id)
    {
        // 🚀 FIX: Search by integer ID OR string UUID from phone!
        $originalOrder = Order::with(['items', 'payments'])
            ->where('id', $id)
            ->orWhere('uuid', $id)
            ->firstOrFail();

        if ($originalOrder->status === 'refunded' || $originalOrder->preparation_status === 'cancelled') {
            return response()->json([
                'message' => 'Cette commande a déjà fait l\'objet d\'un avoir.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Mark original order as cancelled
            $originalOrder->status = 'refunded';
            $originalOrder->preparation_status = 'cancelled'; // 👈 Removes from KDS!
            $originalOrder->save();

            // Fetch last order for continuous SHA-256 hash chaining
            $lastHashOrder = Order::whereNotNull('hash')
                ->where('hash', '!=', '')
                ->orderBy('sequence_number', 'desc')
                ->first();

            $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash))
                ? $lastHashOrder->hash
                : '0000000000000000000000000000000000000000000000000000000000000000';

            $lastSeqOrder = Order::orderBy('sequence_number', 'desc')->first();
            $sequenceNumber = $lastSeqOrder ? ($lastSeqOrder->sequence_number + 1) : 1;

            $subtotalExclVat = -abs($originalOrder->subtotal_excl_vat);
            $vatAmount = -abs($originalOrder->vat_amount);
            $totalInclVat = -abs($originalOrder->total_incl_vat);
            $completedAt = Carbon::now();

            $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            $computedRefundHash = hash('sha256', $dataToHash);

            // Create Avoir Order
            $refundOrder = Order::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => auth('sanctum')->id() ?? $originalOrder->user_id,
                'client_id' => $originalOrder->client_id,
                'customer_name' => "AVOIR / REMBOURSEMENT (#{$originalOrder->sequence_number})",
                'customer_phone' => $originalOrder->customer_phone,
                'order_type' => 'refund',
                'sequence_number' => $sequenceNumber,
                'subtotal_excl_vat' => $subtotalExclVat,
                'vat_amount' => $vatAmount,
                'total_incl_vat' => $totalInclVat,
                'hash' => $computedRefundHash,
                'previous_hash' => $previousHash,
                'completed_at' => $completedAt,
                'preparation_status' => 'cancelled', // Excludes Avoir from KDS
                'status' => 'completed',
            ]);

            foreach ($originalOrder->items as $item) {
                $refundOrder->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => "AVOIR: {$item->product_name}",
                    'quantity' => -abs($item->quantity),
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                    'subtotal' => -abs($item->subtotal),
                ]);
            }

            foreach ($originalOrder->payments as $payment) {
                $refundOrder->payments()->create([
                    'amount' => -abs($payment->amount),
                    'method' => $payment->method,
                ]);
            }

            DB::commit();

            // 🚀 FIRE REVERB EVENT -> Kitchen KDS drops the ticket!
            event(new KdsOrderUpdated('order_refunded', $originalOrder));

            return response()->json([
                'success' => true,
                'message' => "Avoir #{$sequenceNumber} créé pour la commande #{$originalOrder->sequence_number} !",
                'refund_order' => $refundOrder,
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Échec du remboursement: ' . $e->getMessage()], 500);
        }
    }
}