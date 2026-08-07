<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'header_image' => 'nullable|string', // Firebase Storage URL
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(int $id)
    {
        return response()->json(Product::with(['category', 'variants'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'stock_quantity' => 'sometimes|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'header_image' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($validator->validated());

        return response()->json($product);
    }

    public function destroy(int $id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
