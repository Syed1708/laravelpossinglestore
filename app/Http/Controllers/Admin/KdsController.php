<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Events\KdsOrderUpdated;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

class KdsController extends Controller
{
    public function chefIndex()
    {
        return view('admin.kds.chef');
    }

    public function packerIndex()
    {
        return view('admin.kds.packer');
    }

    /**
     * API: Get active hot orders for the Chef (Filters out Drinks!).
     */
    public function getChefOrders()
    {
        $orders = Order::whereIn('preparation_status', ['pending', 'preparing'])
            ->with(['items' => function ($query) {
                $query->whereHas('product.category', function ($subQuery) {
                    $subQuery->where('name', '!=', 'Boissons Gazeuses')
                             ->where('name', '!=', 'Eaux & Jus')
                             ->where('name', '!=', 'Café & Boissons Chaudes');
                });
            }])
            ->orderBy('completed_at', 'asc')
            ->get();

        $filteredOrders = $orders->filter(function ($order) {
            return $order->items->count() > 0;
        })->values();

        return response()->json($filteredOrders);
    }

    /**
     * API: Get active orders for the Packer (Includes Drinks & calculates has_kitchen_items).
     */
    public function getPackerOrders()
    {
        $orders = Order::whereIn('preparation_status', ['pending', 'preparing', 'ready'])
            ->with('items.product.category') 
            ->orderBy('completed_at', 'asc')
            ->get();

        foreach ($orders as $order) {
            $order->has_kitchen_items = $order->items->contains(function ($item) {
                $categoryName = $item->product->category->name ?? '';
                return !in_array($categoryName, [
                    'Boissons Gazeuses',
                    'Eaux & Jus',
                    'Café & Boissons Chaudes'
                ]);
            });
        }

        return response()->json($orders);
    }

    /**
     * API: Toggle item status.
     * 🚀 THE FIX: Uses implicit route model binding (OrderItem $item) instead of plain IDs! [1.1.2]
     */
    public function toggleItemStatus(Request $request, OrderItem $item)
    {
        $newStatus = $item->item_status === 'pending' ? 'done' : 'pending';
        
        $item->update(['item_status' => $newStatus]);

        event(new KdsOrderUpdated('item_toggled'));

        return response()->json(['success' => true, 'new_status' => $newStatus]);
    }

    /**
     * API: Update order status.
     * 🚀 THE FIX: Uses implicit route model binding (Order $order) instead of plain IDs! [1.1.2]
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,delivered',
        ]);

        $order->update(['preparation_status' => $request->status]);

        event(new KdsOrderUpdated('order_status_updated'));

        return response()->json(['success' => true, 'new_status' => $order->preparation_status]);
    }
}