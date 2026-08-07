<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['user', 'items.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        // Attach remaining stock per item for the admin dashboard view
        $orders->getCollection()->transform(function (Order $order) {
            $order->items->each(function ($item) {
                $item->stock_remaining = $item->variant_id
                    ? $item->variant?->stock
                    : $item->product?->stock_quantity;
            });
            return $order;
        });

        return response()->json($orders);
    }

    public function show(int $id)
    {
        return response()->json(
            Order::with(['user', 'items.product', 'items.variant', 'payments'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,paid,shipped,delivered,cancelled,failed',
            'tracking_number' => 'nullable|string|unique:orders,tracking_number,' . $order->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($validator->validated());

        return response()->json($order->fresh(['user', 'items.product']));
    }
}
