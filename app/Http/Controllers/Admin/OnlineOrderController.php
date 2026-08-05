<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Events\KdsOrderUpdated;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OnlineOrderController extends Controller
{
    public function index()
    {
        return view('admin.orders.online');
    }

    /**
     * API: Get all active online orders for the admin management screen
     */
    public function getOnlineOrders()
    {
        $orders = Order::whereIn('preparation_status', ['not_accepted', 'accepted', 'preparing', 'ready'])
            ->where('order_type', 'click_and_collect')
            ->with(['items', 'client'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * API: Admin Accept Online Order & Set Preparation Time (15m, 30m, 45m)
     */
    public function acceptOrder(Request $request, Order $order)
    {
        $request->validate([
            'prep_time' => 'required|integer|min:5|max:180', // minutes
        ]);

        $prepTimeMins = (int) $request->prep_time;
        $estimatedReadyAt = Carbon::now()->addMinutes($prepTimeMins);

        $order->update([
            'preparation_status' => 'accepted',
            'estimated_prep_time' => $prepTimeMins,
            'estimated_ready_at' => $estimatedReadyAt,
        ]);

        // 🚀 Broadcast to Client Live Tracker & Kitchen KDS Screens
        event(new KdsOrderUpdated('new_orders_synced', $order));

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->sequence_number} accepted! Estimated ready in {$prepTimeMins} mins.",
            'order' => $order,
        ]);
    }

    /**
     * API: Admin Reject Online Order
     */
    /**
     * API: Admin Reject Online Order ➔ Triggers AUTOMATIC STRIPE REFUND!
     */
    /**
     * API: Admin Reject Online Order ➔ Triggers AUTOMATIC STRIPE REFUND via payment_intent_id!
     */
    public function rejectOrder(Request $request, Order $order)
    {
        if ($order->status === 'refunded') {
            return response()->json(['message' => 'Cette commande a déjà été remboursée.'], 400);
        }

        DB::beginTransaction();
        try {
            // 🚀 1. DIRECT STRIPE REFUND USING payment_intent_id
            if (!empty($order->payment_intent_id)) {
                $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET');
                \Stripe\Stripe::setApiKey($stripeSecret);

                \Stripe\Refund::create([
                    'payment_intent' => $order->payment_intent_id,
                    'reason'         => 'requested_by_customer',
                ]);
            }

            // 2. Update Order Status in Database
            $order->update([
                'preparation_status' => 'cancelled',
                'status'             => 'refunded',
            ]);

            DB::commit();

            // 3. Broadcast live WebSocket updates
            event(new KdsOrderUpdated('order_refunded', $order));

            return response()->json([
                'success' => true,
                'message' => "Order #{$order->sequence_number} rejected & 100% refunded on Stripe!",
                'order'   => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Stripe refund on rejection failed: ' . $e->getMessage());

            // Still cancel order in DB if Stripe refund fails or throws error
            $order->update([
                'preparation_status' => 'cancelled',
                'status'             => 'refunded',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Order #{$order->sequence_number} cancelled. (Stripe Error: " . $e->getMessage() . ")",
            ]);
        }
    }
}
