<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderSyncController extends Controller
{
    /**
     * Synchronize bulk orders from the offline-first client.
     */
    public function sync(Request $request)
    {
        $user = $request->user();

        // 1. Ensure the user belongs to a store
        if (!$user->store_id) {
            return response()->json([
                'error' => 'Unauthorized. This user is not assigned to a physical store.'
            ], 403);
        }

        // 2. Validate the incoming array payload
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.uuid' => 'required|uuid',
            'orders.*.sequence_number' => 'required|integer',
            'orders.*.subtotal_excl_vat' => 'required|numeric',
            'orders.*.vat_amount' => 'required|numeric',
            'orders.*.total_incl_vat' => 'required|numeric',
            'orders.*.completed_at' => 'required|date',
            'orders.*.hash' => 'nullable|string|max:64',
            'orders.*.previous_hash' => 'nullable|string|max:64',
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

        // 3. Process each order securely inside a Database Transaction
        foreach ($request->orders as $orderData) {
            DB::beginTransaction();
            try {
                // Prevent duplicate processing if the tablet sends an already-synced order
                $exists = Order::where('uuid', $orderData['uuid'])->exists();
                if ($exists) {
                    $syncedUuids[] = $orderData['uuid'];
                    DB::rollBack();
                    continue;
                }

                // Create the core Order
                $order = Order::create([
                    'uuid' => $orderData['uuid'],
                    'store_id' => $user->store_id,
                    'user_id' => $user->id,
                    'sequence_number' => $orderData['sequence_number'],
                    'subtotal_excl_vat' => $orderData['subtotal_excl_vat'],
                    'vat_amount' => $orderData['vat_amount'],
                    'total_incl_vat' => $orderData['total_incl_vat'],
                    'hash' => $orderData['hash'] ?? null,
                    'previous_hash' => $orderData['previous_hash'] ?? null,
                    'completed_at' => $orderData['completed_at'],
                ]);

                // Create individual Order Items
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

                // Create individual Payments
                foreach ($orderData['payments'] as $paymentData) {
                    $order->payments()->create([
                        'amount' => $paymentData['amount'],
                        'method' => $paymentData['method'],
                    ]);
                }

                DB::commit();
                $syncedUuids[] = $orderData['uuid'];

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to sync order {$orderData['uuid']}: " . $e->getMessage());
                $failedUuids[$orderData['uuid']] = $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Synchronization complete',
            'synced_uuids' => $syncedUuids,
            'failed_uuids' => $failedUuids,
        ], 200);
    }
}
