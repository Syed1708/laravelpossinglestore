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
     * POST /api/v1/pos/refund/{id}
     * NF525 Legal Refund: Leaves original order untouched and creates a NEW negative Avoir ticket (#162)!
     */
    public function refundOrder(Request $request, $id)
    {
        $originalOrder = Order::with(['items', 'payments'])->findOrFail($id);

        if ($originalOrder->status === 'refunded') {
            return response()->json([
                'message' => 'Cette commande a déjà fait l\'objet d\'un avoir.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Mark original order as refunded (Without altering its financial values or hash!)
            $originalOrder->update(['status' => 'refunded']);

            // 2. Fetch last order to calculate NEXT sequential number and SHA-256 chain!
            $lastOrder = Order::orderBy('sequence_number', 'desc')->first();
            $sequenceNumber = $lastOrder ? ($lastOrder->sequence_number + 1) : 1; // e.g. #162
            $previousHash = $lastOrder ? $lastOrder->hash : '0000000000000000000000000000000000000000000000000000000000000000';

            // 3. Compute Negative Financials for Avoir (Credit Note)
            $subtotalExclVat = -abs($originalOrder->subtotal_excl_vat);
            $vatAmount = -abs($originalOrder->vat_amount);
            $totalInclVat = -abs($originalOrder->total_incl_vat);
            $completedAt = Carbon::now();

            // 4. Calculate SHA-256 Hash for the Refund Order
            $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            $computedRefundHash = hash('sha256', $dataToHash);

            // 🚀 5. CREATE BRAND NEW NEGATIVE ORDER (Ticket #162 - Avoir)
            $refundOrder = Order::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => auth('sanctum')->id() ?? $originalOrder->user_id,
                'client_id' => $originalOrder->client_id,
                'customer_name' => "AVOIR / REMBOURSEMENT (#{$originalOrder->sequence_number})",
                'customer_phone' => $originalOrder->customer_phone,
                'order_type' => 'refund',
                'sequence_number' => $sequenceNumber, // 👈 New Sequential Number e.g. #162!
                'subtotal_excl_vat' => $subtotalExclVat,
                'vat_amount' => $vatAmount,
                'total_incl_vat' => $totalInclVat,
                'hash' => $computedRefundHash,
                'previous_hash' => $previousHash,
                'completed_at' => $completedAt,
                'preparation_status' => 'cancelled',
                'status' => 'completed', // The credit note transaction is complete
            ]);

            // 🚀 6. CREATE NEGATIVE ITEMS
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

            // 🚀 7. CREATE NEGATIVE PAYMENTS
            foreach ($originalOrder->payments as $payment) {
                $refundOrder->payments()->create([
                    'amount' => -abs($payment->amount),
                    'method' => $payment->method,
                ]);
            }

            DB::commit();

            // 🚀 8. FIRE REVERB EVENT -> Cancels order on Chef & Packer screens!
            event(new KdsOrderUpdated('order_refunded', $originalOrder));

            return response()->json([
                'success' => true,
                'message' => "Avoir #{$sequenceNumber} créé pour la commande #{$originalOrder->sequence_number} !",
                'refund_order' => $refundOrder,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Échec du remboursement: ' . $e->getMessage()
            ], 500);
        }
    }
}