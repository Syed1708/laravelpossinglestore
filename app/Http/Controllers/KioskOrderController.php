<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Events\KdsOrderUpdated;
use App\Helpers\StoreHoursHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KioskOrderController extends Controller
{
    public function storeKioskOrder(Request $request)
    {
        if (!StoreHoursHelper::isOpen()) {
            return response()->json([
                'success' => false,
                'message' => StoreHoursHelper::getClosedMessage()
            ], 403);
        }

        // 🚀 1. VALIDATE NOTES & EXTRAS ARRAYS
        $validated = $request->validate([
            'cart'              => 'required|array|min:1',
            'cart.*.id'         => 'required|exists:products,id',
            'cart.*.quantity'   => 'required|integer|min:1',
            'cart.*.notes'      => 'nullable|array',      // 🚀 Explicitly allow notes array!
            'cart.*.extraPrice' => 'nullable|numeric',    // 🚀 Explicitly allow extraPrice!
            'order_type'        => 'required|in:kiosk_eat_in,kiosk_takeaway,dine_in,takeaway',
            'payment_choice'    => 'required|in:pay_at_counter,card_terminal',
            'customer_name'     => 'nullable|string|max:100',
            'customer_phone'    => 'nullable|string|max:50',
        ]);

        // 🚀 2. CARD VS COUNTER STATUS LOGIC
        $isCardPaid = $validated['payment_choice'] === 'card_terminal';

        DB::beginTransaction();
        try {
            $subtotalExclVat = 0;
            $vatAmount = 0;
            $totalInclVat = 0;
            $orderItems = [];

            foreach ($validated['cart'] as $itemData) {
                $product = Product::findOrFail($itemData['id']);
                $quantity = (int) $itemData['quantity'];
                $basePrice = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice = (float) ($itemData['extraPrice'] ?? 0);
                $unitPriceWithExtras = $basePrice + $extraPrice;
                $vatRate = (float) ($product->vat_rate ?? 10.0);

                $itemTotalTtc = $unitPriceWithExtras * $quantity;
                $itemSubtotalHt = $itemTotalTtc / (1 + ($vatRate / 100));
                $itemVat = $itemTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount += $itemVat;
                $totalInclVat += $itemTotalTtc;

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'notes'        => $itemData['notes'] ?? null, // 🚀 Step notes array ("Sauce: Algérienne | Meat: Beef")
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPriceWithExtras,
                    'vat_rate'     => $vatRate,
                    'subtotal'     => $itemTotalTtc,
                ];
            }

            $lastSeqOrder = Order::orderBy('sequence_number', 'desc')->lockForUpdate()->first();
            $sequenceNumber = $lastSeqOrder ? ($lastSeqOrder->sequence_number + 1) : 101;
            $completedAt = Carbon::now();

            $lastHashOrder = Order::whereNotNull('hash')->where('hash', '!=', '')->orderBy('sequence_number', 'desc')->first();
            $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash)) ? $lastHashOrder->hash : '0000000000000000000000000000000000000000000000000000000000000000';

            $dataToHash = "{$sequenceNumber}|" . number_format($subtotalExclVat, 2, '.', '') . "|" . number_format($vatAmount, 2, '.', '') . "|" . number_format($totalInclVat, 2, '.', '') . "|{$completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";
            $currentHash = hash('sha256', $dataToHash);

            // 🚀 3. SET STATUS: Card = 'accepted' (Immediate KDS) | Counter = 'not_accepted' (Pay Cash First)
            $order = Order::create([
                'uuid'               => (string) \Illuminate\Support\Str::uuid(),
                'customer_name'      => $validated['customer_name'] ?? 'Kiosk Customer',
                'customer_phone'     => $validated['customer_phone'] ?? null,
                'order_type'         => in_array($validated['order_type'], ['kiosk_eat_in', 'dine_in']) ? 'dine_in' : 'takeaway',
                'sequence_number'    => $sequenceNumber,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                'vat_amount'         => round($vatAmount, 2),
                'total_incl_vat'     => round($totalInclVat, 2),
                'hash'               => $currentHash,
                'previous_hash'      => $previousHash,
                'completed_at'       => $completedAt,
                'preparation_status' => $isCardPaid ? 'accepted' : 'not_accepted', // 🚀 Card = Auto-Sent to Kitchen KDS!
                'status'             => $isCardPaid ? 'completed' : 'pending',
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'notes'        => $item['notes'], // 🚀 Saved cleanly in order_items.notes
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'vat_rate'     => $item['vat_rate'],
                    'subtotal'     => $item['subtotal'],
                ]);

                // Recipe stock reduction
                $recipes = Recipe::where('product_id', $item['product_id'])->get();
                foreach ($recipes as $recipe) {
                    Ingredient::where('id', $recipe->ingredient_id)->decrement('stock_level', $item['quantity'] * $recipe->quantity);
                }
            }


        // 🚀 FIX: ONLY CREATE PAYMENT RECORD IF PAID BY CARD AT KIOSK!
        // Pay-at-Counter orders do NOT create a Payment record until cashier collects cash/card.
        if ($isCardPaid) {
            Payment::create([
                'order_id' => $order->id,
                'amount'   => round($totalInclVat, 2),
                'method'   => 'card',
            ]);
        }
            DB::commit();

            // Broadcast to KDS & Online Dispatcher
            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (\Exception $e) {}

            return response()->json([
                'success'       => true,
                'ticket_number' => "T-{$sequenceNumber}",
                'total'         => round($totalInclVat, 2),
                'order'         => $order,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kiosk Order Creation Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to process kiosk order.'], 500);
        }
    }

/**
 * API for Web POS: Fetch all unpaid Kiosk orders waiting for counter payment
 */
public function getUnpaidKioskOrders()
{
    $orders = Order::where('status', 'pending')
        ->whereIn('preparation_status', ['not_accepted', 'pending'])
        ->with(['items', 'client'])
        ->latest()
        ->get();

    return response()->json($orders);
}

/**
 * API for Web POS Cashier: Accept Payment for a Kiosk Order at the Counter
 */
public function payKioskOrderAtCounter(Request $request, Order $order)
{
    $validated = $request->validate([
        'payment_method' => 'required|in:cash,card,split',
        'cash_given'     => 'nullable|numeric',
    ]);

    if ($order->status === 'completed' || $order->status === 'paid') {
        return response()->json(['message' => 'This order has already been paid.'], 422);
    }

    DB::beginTransaction();
    try {
        // 1. Update Order & Preparation Status
        $order->update([
            'status'             => 'completed',
            'preparation_status' => 'accepted', // 🚀 Auto-sent to Kitchen KDS!
        ]);

        // 2. Create Payment Record
        Payment::create([
            'order_id' => $order->id,
            'amount'   => $order->total_incl_vat,
            'method'   => $validated['payment_method'],
        ]);

        DB::commit();

        // 3. Broadcast WebSocket Event to Kitchen KDS (Chef & Packer)
        try {
            event(new KdsOrderUpdated('new_orders_synced', $order));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => "Payment received for Ticket #{$order->sequence_number}. Order sent to kitchen!",
            'order'   => $order,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to process payment.'], 500);
    }
}
    /**
 * API for Web POS: Void/Cancel an abandoned unpaid Kiosk order & restore ingredient stocks
 */
public function cancelUnpaidKioskOrder(Request $request, Order $order)
{
    if ($order->status === 'completed' || $order->status === 'paid') {
        return response()->json(['message' => 'Cannot cancel an order that has already been paid.'], 422);
    }

    DB::beginTransaction();
    try {
        // 1. Update Order Status to Cancelled
        $order->update([
            'status'             => 'cancelled',
            'preparation_status' => 'cancelled',
        ]);

        // 🚀 2. RESTORE INGREDIENT STOCKS
        foreach ($order->items as $item) {
            $recipes = Recipe::where('product_id', $item->product_id)->get();
            foreach ($recipes as $recipe) {
                Ingredient::where('id', $recipe->ingredient_id)
                    ->increment('stock_level', $item->quantity * $recipe->quantity);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Ticket #{$order->sequence_number} voided and ingredient stocks restored.",
            'order'   => $order,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to cancel unpaid kiosk order.'], 500);
    }
}
 
}