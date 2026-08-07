<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $items = CartItem::with(['product', 'variant'])
            ->where('user_id', $request->user()->id)
            ->get();

        $total = $items->sum(fn (CartItem $item) => $item->lineTotal());

        return response()->json([
            'items' => $items,
            'total' => round($total, 2),
        ]);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);

        $availableStock = $product->stock_quantity;
        if ($request->variant_id) {
            $variant = ProductVariant::findOrFail($request->variant_id);
            $availableStock = $variant->stock;
        }

        if ($availableStock < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock available'], 422);
        }

        $item = CartItem::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
            ],
            []
        );

        $item->quantity = ($item->wasRecentlyCreated ? 0 : $item->quantity) + $request->quantity;
        $item->save();

        return response()->json($item->load(['product', 'variant']), 201);
    }

    public function update(Request $request, int $id)
    {
        $item = CartItem::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update(['quantity' => $request->quantity]);

        return response()->json($item->load(['product', 'variant']));
    }

    public function remove(Request $request, int $id)
    {
        $item = CartItem::where('user_id', $request->user()->id)->findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Removed from cart']);
    }
}
