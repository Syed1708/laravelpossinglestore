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
     * API: Get active hot food orders for Chef Screen (Excludes beverages).
     */
    public function getChefOrders(): JsonResponse
    {
        $excludedCategories = [
            'Boissons Gazeuses',
            'Eaux & Jus',
            'Café & Boissons Chaudes',
            'Soft Drinks',
            'Beverages',
            'Hot Drinks',
            'Drinks',
        ];

        $orders = Order::whereIn('preparation_status', ['accepted', 'pending', 'preparing'])
            ->with(['items' => function ($query) use ($excludedCategories) {
                $query->whereHas('product.category', function ($subQuery) use ($excludedCategories) {
                    $subQuery->whereNotIn('name', $excludedCategories);
                });
            }, 'client'])
            ->orderBy('completed_at', 'asc')
            ->get();

        $filteredOrders = $orders->filter(fn($order) => $order->items->count() > 0)->values();

        return response()->json($filteredOrders, 200);
    }

    /**
     * API: Get all active orders for Packer Screen (Includes cold beverages & flags kitchen items).
     */
    public function getPackerOrders(): JsonResponse
    {
        $excludedCategories = [
            'Boissons Gazeuses',
            'Eaux & Jus',
            'Café & Boissons Chaudes',
            'Soft Drinks',
            'Beverages',
            'Hot Drinks',
            'Drinks',
        ];

        $orders = Order::whereIn('preparation_status', ['accepted', 'pending', 'preparing', 'ready'])
            ->with(['items.product.category', 'client'])
            ->orderBy('completed_at', 'asc')
            ->get();

        foreach ($orders as $order) {
            $order->has_kitchen_items = $order->items->contains(function ($item) use ($excludedCategories) {
                $categoryName = $item->product->category->name ?? '';
                return !in_array($categoryName, $excludedCategories);
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
     * API: Update preparation status for an order
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