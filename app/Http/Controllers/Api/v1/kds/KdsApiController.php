<?php

namespace App\Http\Controllers\Api\v1\kds;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Events\KdsOrderUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KdsApiController extends Controller
{
    /**
     * API: Get active hot food orders for Chef Screen.
     * 🚀 100% DYNAMIC: Queries categories where show_on_chef_kds is true (Zero hardcoded names!)
     */
    public function getChefOrders(): JsonResponse
    {
        $orders = Order::whereIn('preparation_status', ['accepted', 'pending', 'preparing'])
            ->with(['items' => function ($query) {
                $query->whereHas('product.category', function ($subQuery) {
                    $subQuery->where('show_on_chef_kds', true);
                });
            }, 'client'])
            ->orderBy('completed_at', 'asc')
            ->get();

        // Only return tickets that have at least 1 item for the chef to cook
        $filteredOrders = $orders->filter(fn($order) => $order->items->isNotEmpty())->values();

        return response()->json($filteredOrders, 200);
    }

    /**
     * API: Get active orders for Packer Screen.
     */
    public function getPackerOrders(): JsonResponse
    {
        $orders = Order::whereIn('preparation_status', ['accepted', 'pending', 'preparing', 'ready'])
            ->with(['items.product.category', 'client'])
            ->orderBy('completed_at', 'asc')
            ->get();

        foreach ($orders as $order) {
            $order->has_kitchen_items = $order->items->contains(function ($item) {
                return (bool) ($item->product->category->show_on_chef_kds ?? true);
            });
        }

        return response()->json($orders, 200);
    }

    /**
     * API: Toggle item preparation status (pending <-> done)
     */
    public function toggleItemStatus(Request $request, OrderItem $item): JsonResponse
    {
        $newStatus = ($item->item_status === 'pending') ? 'done' : 'pending';
        $item->update(['item_status' => $newStatus]);

        event(new KdsOrderUpdated('item_toggled', $item->order));

        return response()->json([
            'success'    => true,
            'new_status' => $newStatus,
            'item_id'    => $item->id,
            'message'    => 'Item status updated.',
        ], 200);
    }

    /**
     * API: Update preparation status for an entire order
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:not_accepted,accepted,pending,preparing,ready,delivered,cancelled'],
        ]);

        $order->update(['preparation_status' => $validated['status']]);

        event(new KdsOrderUpdated('order_status_updated', $order));

        return response()->json([
            'success'    => true,
            'new_status' => $order->preparation_status,
            'order_id'   => $order->id,
            'message'    => "Order preparation status updated to {$order->preparation_status}.",
        ], 200);
    }
}