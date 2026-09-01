<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponses;

    public function stats(Request $request)
    {
        $totalSales = Order::whereIn('status', ['paid', 'shipped', 'completed'])->sum('total');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalCategories = Category::count();
        $lowStockProducts = Product::where('stock', '<=', $request->integer('low_stock_threshold', 10))->get();
        $recentOrders = Order::with('user', 'items')->latest()->take(10)->get();

        $data = [
            'total_sales' => round((float) $totalSales, 2),
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_categories' => $totalCategories,
            'pending_orders' => $pendingOrders,
            'low_stock_products' => ProductResource::collection($lowStockProducts),
            'recent_orders' => $recentOrders,
        ];

        return $this->successResponse(new DashboardStatsResource($data), 'Dashboard stats retrieved successfully');
    }

    public function lowStock()
    {
        $threshold = request()->integer('threshold', 10);
        $products = Product::where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->paginate(request()->integer('per_page', 15));

        return $this->paginatedResponse(
            ProductResource::collection($products),
            'Low stock products retrieved successfully'
        );
    }
}
