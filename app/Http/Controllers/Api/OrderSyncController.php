<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Events\KdsOrderUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrderSyncController extends Controller
{
    /**
     * Synchronize bulk orders from Web POS & Expo Mobile App
     * Handles sequential numbering (1,2,3...) and SHA-256 cryptographic hash chaining.
     */
    public function sync(Request $request)
    {
        $user = $request->user();

        // 1. Validate payload structure
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.uuid' => 'required|uuid',
            'orders.*.subtotal_excl_vat' => 'required|numeric',
            'orders.*.vat_amount' => 'required|numeric',
            'orders.*.total_incl_vat' => 'required|numeric',
            'orders.*.completed_at' => 'required|date',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_name' => 'required|string',
            'orders.*.items.*.quantity' => 'required|integer',
            'orders.*.items.*.unit_price' => 'required|numeric',
            'orders.*.items.*.vat_rate' => 'required|numeric',
            'orders.*.items.*.subtotal' => 'required|numeric',
            'orders.*.payments' => 'required|array|min:1',
            'orders.*.payments.*.amount' => 'required|numeric',
            'orders.*.payments.*.method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $syncedUuids = [];
        $failedUuids = [];
        $createdOrders = []; // 🚀 1. Array to hold created order sequence numbers

        // 2. Process each order securely inside a Database Transaction
        foreach ($request->orders as $orderData) {
            DB::beginTransaction();
            try {
                // Prevent duplicate processing if ticket was already synced
                $exists = Order::where('uuid', $orderData['uuid'])->exists();
                if ($exists) {
                    $syncedUuids[] = $orderData['uuid'];
                    DB::rollBack();
                    continue;
                }

                // 🚀 3. AUTO-CALCULATE UNBROKEN SEQUENTIAL NUMBER (1, 2, 3, 4...)
                $lastOrder = Order::orderBy('sequence_number', 'desc')->first();
                $sequenceNumber = $lastOrder ? ($lastOrder->sequence_number + 1) : 1;

                // 🚀 4. CALCULATE CRYPTOGRAPHIC SHA-256 HASH CHAIN (NF525 Compliance)
                $previousHash = $lastOrder ? $lastOrder->hash : '0000000000000000000000000000000000000000000000000000000000000000';

                $subtotalExclVat = (float) $orderData['subtotal_excl_vat'];
                $vatAmount = (float) $orderData['vat_amount'];
                $totalInclVat = (float) $orderData['total_incl_vat'];
                $completedAt = Carbon::parse($orderData['completed_at']);

                // Build the NF525 SHA-256 string
                $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
                $computedHash = hash('sha256', $dataToHash);

                $finalHash = !empty($orderData['hash']) ? $orderData['hash'] : $computedHash;
                $finalPreviousHash = !empty($orderData['previous_hash']) ? $orderData['previous_hash'] : $previousHash;

                // 🚀 5. Create Core Order
                $order = Order::create([
                    'uuid' => $orderData['uuid'],
                    'user_id' => $user->id ?? null,
                    'client_id' => $orderData['client_id'] ?? null,
                    'customer_name' => $orderData['customer_name'] ?? 'Walk-in Customer',
                    'customer_phone' => $orderData['customer_phone'] ?? null,
                    'order_type' => $orderData['order_type'] ?? 'dine_in',
                    'sequence_number' => $sequenceNumber,
                    'subtotal_excl_vat' => $subtotalExclVat,
                    'vat_amount' => $vatAmount,
                    'total_incl_vat' => $totalInclVat,
                    'hash' => $finalHash,
                    'previous_hash' => $finalPreviousHash,
                    'completed_at' => $completedAt,
                    'preparation_status' => 'pending',
                    'status' => 'completed',
                ]);

                // 🚀 6. Create Order Items
                foreach ($orderData['items'] as $itemData) {
                    $order->items()->create([
                        'product_id' => $itemData['product_id'] ?? null,
                        'product_name' => $itemData['product_name'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'vat_rate' => $itemData['vat_rate'],
                        'subtotal' => $itemData['subtotal'],
                    ]);
                }

                // 🚀 7. Create Payments
                foreach ($orderData['payments'] as $paymentData) {
                    $order->payments()->create([
                        'amount' => $paymentData['amount'],
                        'method' => $paymentData['method'],
                    ]);
                }

                DB::commit();

                // 🚀 8. BROADCAST WEBSOCKET EVENT TO KITCHEN (Chef & Packer screens light up live!)
                event(new KdsOrderUpdated('new_orders_synced', $order));

                $syncedUuids[] = $orderData['uuid'];

                // 🚀 2. Store created order details for response
                $createdOrders[] = [
                    'uuid' => $order->uuid,
                    'id' => $order->id,
                    'sequence_number' => $order->sequence_number, // 👈 14
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to sync POS order {$orderData['uuid']}: " . $e->getMessage());
                $failedUuids[$orderData['uuid']] = $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Synchronization complete',
            'synced_uuids' => $syncedUuids,
            'failed_uuids' => $failedUuids,
            'orders' => $createdOrders, // 👈 SENT TO NEXT.JS!

        ], 200);
    }
}
