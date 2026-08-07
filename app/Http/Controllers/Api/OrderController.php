<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::with('items.product', 'items.variant')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Public tracking endpoint - by order id or tracking number.
     */
    public function track(string $id)
    {
        $order = Order::where('tracking_number', $id)
            ->orWhere('id', $id)
            ->select('id', 'status', 'tracking_number', 'total', 'created_at', 'updated_at')
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }
}
