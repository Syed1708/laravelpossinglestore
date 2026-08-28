<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Fiscal\FiscalLedgerService;
use App\Services\Inventory\StockService;
use App\Services\Orders\SequenceService;
use App\Events\KdsOrderUpdated;
use App\Helpers\StoreHoursHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;

class KioskOrderController extends Controller
{
    public function __construct(
        protected StockService $stockService,
        protected FiscalLedgerService $fiscalService,
        protected SequenceService $sequenceService
    ) {}

    /**
     * Store new order from in-store self-service touchscreen kiosk
     */
    public function storeKioskOrder(Request $request): JsonResponse
    {
        if (!StoreHoursHelper::isOpen()) {
            return response()->json([
                'success' => false,
                'message' => StoreHoursHelper::getClosedMessage()
            ], 403);
        }

        $validated = $request->validate([
            'cart'              => 'required|array|min:1',
            'cart.*.id'         => 'required|exists:products,id',
            'cart.*.quantity'   => 'required|integer|min:1',
            'cart.*.notes'      => 'nullable|array',
            'cart.*.extraPrice' => 'nullable|numeric|min:0',
            'order_type'        => 'required|in:kiosk_eat_in,kiosk_takeaway,dine_in,takeaway',
            'payment_choice'    => 'required|in:pay_at_counter,card_terminal',
            'customer_name'     => 'nullable|string|max:100',
            'customer_phone'    => 'nullable|string|max:50',
        ]);

        $isCardPaid = $validated['payment_choice'] === 'card_terminal';

        DB::beginTransaction();
        try {
            $subtotalExclVat = 0;
            $vatAmount       = 0;
            $totalInclVat    = 0;
            $orderItems      = [];
            $stockItems      = [];

            foreach ($validated['cart'] as $itemData) {
                $product             = Product::findOrFail($itemData['id']);
                $quantity            = (int) $itemData['quantity'];
                $basePrice           = (float) ($product->price ?? $product->unit_price ?? 0);
                $extraPrice          = (float) ($itemData['extraPrice'] ?? 0);
                $unitPriceWithExtras = $basePrice + $extraPrice;
                $vatRate             = (float) ($product->vat_rate ?? 10.0);

                $itemTotalTtc   = $unitPriceWithExtras * $quantity;
                $itemSubtotalHt = $itemTotalTtc / (1 + ($vatRate / 100));
                $itemVat        = $itemTotalTtc - $itemSubtotalHt;

                $subtotalExclVat += $itemSubtotalHt;
                $vatAmount       += $itemVat;
                $totalInclVat    += $itemTotalTtc;

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'notes'        => $itemData['notes'] ?? null,
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPriceWithExtras,
                    'vat_rate'     => $vatRate,
                    'subtotal'     => $itemTotalTtc,
                ];

                $stockItems[] = [
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                ];
            }

            // 🚀 1. ATOMIC SEQUENCE NUMBER & NF525 CRYPTOGRAPHIC HASH
            $sequenceNumber = $this->sequenceService->getNextSequenceNumber();
            $signature      = $this->fiscalService->generateSignature(
                $sequenceNumber,
                $subtotalExclVat,
                $vatAmount,
                $totalInclVat
            );

            // 🚀 2. CREATE KIOSK ORDER
            $order = Order::create([
                'uuid'               => (string) Str::uuid(),
                'customer_name'      => $validated['customer_name'] ?? 'Kiosk Customer',
                'customer_phone'     => $validated['customer_phone'] ?? null,
                'order_type'         => in_array($validated['order_type'], ['kiosk_eat_in', 'dine_in']) ? 'dine_in' : 'takeaway',
                'sequence_number'    => $sequenceNumber,
                'subtotal_excl_vat'  => round($subtotalExclVat, 2),
                'vat_amount'         => round($vatAmount, 2),
                'total_incl_vat'     => round($totalInclVat, 2),
                'hash'               => $signature['hash'],
                'previous_hash'      => $signature['previous_hash'],
                'completed_at'       => $signature['completed_at'],
                'preparation_status' => $isCardPaid ? 'accepted' : 'not_accepted',
                'status'             => $isCardPaid ? 'completed' : 'pending',
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // 🚀 3. ATOMIC STOCK DEDUCTION VIA STOCK SERVICE (Single 1x deduction)
            $this->stockService->decrementStockForItems($stockItems);

            // 🚀 4. RECORD PAYMENT IF CARD PAID AT KIOSK TERMINAL
            if ($isCardPaid) {
                Payment::create([
                    'order_id' => $order->id,
                    'amount'   => round($totalInclVat, 2),
                    'method'   => 'card_terminal',
                ]);
            }

            DB::commit();

            // Broadcast to KDS
            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (Throwable $e) {
                Log::warning('WebSocket broadcast for Kiosk order failed: ' . $e->getMessage());
            }

            return response()->json([
                'success'       => true,
                'ticket_number' => "T-{$sequenceNumber}",
                'total'         => round($totalInclVat, 2),
                'order'         => $order,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Kiosk Order Creation Exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to process kiosk order.'], 500);
        }
    }

    /**
     * API for Web POS: Fetch all unpaid Kiosk orders waiting for counter payment
     */
    public function getUnpaidKioskOrders(): JsonResponse
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
    public function payKioskOrderAtCounter(Request $request, Order $order): JsonResponse
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
            $order->update([
                'status'             => 'completed',
                'preparation_status' => 'accepted', // Auto-send to kitchen
            ]);

            Payment::create([
                'order_id' => $order->id,
                'amount'   => $order->total_incl_vat,
                'method'   => $validated['payment_method'],
            ]);

            DB::commit();

            try {
                event(new KdsOrderUpdated('new_orders_synced', $order));
            } catch (Throwable $e) {}

            return response()->json([
                'success' => true,
                'message' => "Payment received for Ticket #{$order->sequence_number}. Order sent to kitchen!",
                'order'   => $order,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to process payment.'], 500);
        }
    }

    /**
     * API for Web POS: Void/Cancel an abandoned unpaid Kiosk order & restore ingredient stocks
     */
    public function cancelUnpaidKioskOrder(Request $request, Order $order): JsonResponse
    {
        if ($order->status === 'completed' || $order->status === 'paid') {
            return response()->json(['message' => 'Cannot cancel an order that has already been paid.'], 422);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status'             => 'cancelled',
                'preparation_status' => 'cancelled',
            ]);

            // 🚀 RESTORE INGREDIENT STOCKS VIA STOCK SERVICE
            $this->stockService->restoreStockForOrder($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Ticket #{$order->sequence_number} voided and ingredient stocks restored.",
                'order'   => $order,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to cancel unpaid kiosk order.'], 500);
        }
    }
}