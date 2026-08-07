<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->where('is_active', true)->with(['category', 'variants']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderByDesc('created_at')->paginate($request->get('per_page', 12));

        return response()->json($products);
    }

    public function show(int $id)
    {
        $product = Product::with(['category', 'variants', 'ratings.user'])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json($product);
    }
}
