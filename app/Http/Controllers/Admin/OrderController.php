<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'client'])->latest();

        // 1. Search Filter (Ticket # or Customer Name)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('sequence_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        // 2. Order Type Filter (click_and_collect, takeaway, dine_in)
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->input('order_type'));
        }

        // 3. Preparation Status Filter
        if ($request->filled('status')) {
            $query->where('preparation_status', $request->input('status'));
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders'  => $orders,
            'filters' => $request->only(['search', 'order_type', 'status']),
        ]);
    }


}
