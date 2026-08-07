<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSales = Order::where('status', '!=', 'failed')
            ->whereIn('status', ['paid', 'shipped', 'delivered'])
            ->sum('total');

        $totalOrders = Order::count();

        $productsRemaining = Product::sum('stock_quantity');

        $lowStock = Product::where('stock_quantity', '<=', 5)
            ->where('is_active', true)
            ->select('id', 'name', 'stock_quantity')
            ->get();

        return response()->json([
            'total_sales' => round($totalSales, 2),
            'total_orders' => $totalOrders,
            'products_remaining' => $productsRemaining,
            'low_stock' => $lowStock,
        ]);
    }
}
