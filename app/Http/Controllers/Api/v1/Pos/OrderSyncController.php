<?php

namespace App\Http\Controllers\Api\v1\Pos;

use App\Events\KdsOrderUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncOrdersRequest;
use App\Models\Order;
use App\Services\Fiscal\FiscalLedgerService;
use App\Services\Inventory\StockService;
use App\Services\LoyaltyService;
use App\Services\Orders\SequenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderSyncController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected FiscalLedgerService $fiscalService,
        protected SequenceService $sequenceService
    ) {}

    /**
     * Synchronize bulk orders from Web POS & Expo Mobile App.
     * Handles gapless sequential numbering, NF525 cryptographic hashing, and safe inventory deductions.
     */
    public function sync(SyncOrdersRequest $request): JsonResponse
    {
        $user = $request->user();

        $syncedUuids   = [];
        $failedUuids   = [];
        $createdOrders = [];

        // 1. Sort orders chronologically so offline batches are chained in correct time sequence
        $ordersToProcess = collect($request->validated()['orders'])->sortBy(function ($order) {
            return Carbon::parse($order['completed_at'])->timestamp;
        })->values()->all();

        // 2. Process each order inside an isolated atomic transaction
        foreach ($ordersToProcess as $orderData) {
            DB::beginTransaction();
            try {
                // 🚀 IDEMPOTENCY CHECK: Prevent duplicate order processing
                $existingOrder = Order::where('uuid', $orderData['uuid'])->first();

                if ($existingOrder) {
                    $incomingStatus = $orderData['preparation_status'] ?? $orderData['status'] ?? null;

                    // Handle status updates / cancellations from offline device
                    if (in_array($incomingStatus, ['cancelled', 'refunded']) && $existingOrder->status !== 'refunded') {
                        $existingOrder->update([
                            'preparation_status' => 'cancelled',
                            'status'             => 'refunded',
                        ]);

                        // 🚀 Restore inventory stock when an order is cancelled/refunded
                        $this->stockService->restoreStockForOrder($existingOrder);

                        DB::commit();

                        // Fire Reverb WebSocket to clear ticket from KDS screens live
                        event(new KdsOrderUpdated('order_refunded', $existingOrder));
                    } else {
                        DB::rollBack();
                    }

                    $syncedUuids[] = $orderData['uuid'];
                    $createdOrders[] = [
                        'uuid'            => $existingOrder->uuid,
                        'id'              => $existingOrder->id,
                        'sequence_number' => $existingOrder->sequence_number,
                    ];
                    continue;
                }

                // 🚀 3. GENERATE ATOMIC SEQUENTIAL TICKET NUMBER (1, 2, 3...)
                $sequenceNumber = $this->sequenceService->getNextSequenceNumber();

                // 🚀 4. GENERATE NF525 SHA-256 CRYPTOGRAPHIC HASH CHAIN
                $subtotalExclVat = (float) $orderData['subtotal_excl_vat'];
                $vatAmount       = (float) $orderData['vat_amount'];
                $totalInclVat    = (float) $orderData['total_incl_vat'];
                $completedAt     = Carbon::parse($orderData['completed_at']);

                $signature = $this->fiscalService->generateSignature(
                    $sequenceNumber,
                    $subtotalExclVat,
                    $vatAmount,
                    $totalInclVat,
                    $completedAt
                );

                // 🚀 5. CREATE CORE ORDER RECORD
                $order = Order::create([
                    'uuid'               => $orderData['uuid'],
                    'user_id'            => $user->id ?? null,
                    'client_id'          => $orderData['client_id'] ?? null,
                    'customer_name'      => $orderData['customer_name'] ?? 'Walk-in Customer',
                    'customer_phone'     => $orderData['customer_phone'] ?? null,
                    'order_type'         => $orderData['order_type'] ?? 'dine_in',
                    'sequence_number'    => $sequenceNumber,
                    'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                    'vat_amount'         => round($vatAmount, 2),
                    'total_incl_vat'     => round($totalInclVat, 2),
                    'hash'               => $signature['hash'],
                    'previous_hash'      => $signature['previous_hash'],
                    'completed_at'       => $signature['completed_at'],
                    'preparation_status' => $orderData['preparation_status'] ?? 'accepted',
                    'status'             => $orderData['status'] ?? 'completed',
                ]);

                // 🚀 6. CREATE ORDER ITEMS
                foreach ($orderData['items'] as $itemData) {
                    $order->items()->create([
                        'product_id'   => $itemData['product_id'] ?? null,
                        'product_name' => $itemData['product_name'],
                        'quantity'     => (int) $itemData['quantity'],
                        'unit_price'   => (float) $itemData['unit_price'],
                        'vat_rate'     => (float) $itemData['vat_rate'],
                        'subtotal'     => (float) $itemData['subtotal'],
                        'notes'        => $itemData['notes'] ?? null,
                    ]);
                }

                // 🚀 7. ATOMIC INVENTORY STOCK DECREMENT (Exactly 1x for POS sales)
                $this->stockService->decrementStockForItems($orderData['items']);

                // 🚀 8. CREATE PAYMENT RECORDS
                foreach ($orderData['payments'] as $paymentData) {
                    $order->payments()->create([
                        'amount' => (float) $paymentData['amount'],
                        'method' => $paymentData['method'],
                    ]);
                }

                // 🚀 9. AWARD LOYALTY POINTS (If client account attached)
                if (!empty($order->client_id)) {
                    LoyaltyService::awardPointsForOrder($order);
                }

                DB::commit();

                // 🚀 10. BROADCAST REAL-TIME WEBSOCKET EVENT TO KITCHEN KDS
                event(new KdsOrderUpdated('new_orders_synced', $order));

                $syncedUuids[] = $orderData['uuid'];
                $createdOrders[] = [
                    'uuid'            => $order->uuid,
                    'id'              => $order->id,
                    'sequence_number' => $order->sequence_number,
                ];

            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("Failed to sync POS order {$orderData['uuid']}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                $failedUuids[$orderData['uuid']] = $e->getMessage();
            }
        }

        return response()->json([
            'success'      => count($failedUuids) === 0,
            'message'      => 'Synchronization processed',
            'synced_uuids' => $syncedUuids,
            'failed_uuids' => $failedUuids,
            'orders'       => $createdOrders,
        ], count($syncedUuids) > 0 ? 200 : 422);
    }
}