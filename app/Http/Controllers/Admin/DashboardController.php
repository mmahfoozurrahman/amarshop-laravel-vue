<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Http\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stats' => [
                    'products' => Product::count(),
                    'active_products' => Product::active()->count(),
                    'customers' => User::customers()->count(),
                    'orders' => Order::count(),
                    'pending_orders' => Order::where('status', 'pending')->count(),
                    'revenue' => (float) Order::paid()->sum('total'),
                ],
                'recent_orders' => AdminOrderResource::collection(
                    Order::with(['user', 'items'])->withCount('items')->latest()->take(5)->get()
                ),
                'low_stock' => ProductResource::collection(
                    Product::with(['brand', 'category'])
                        ->active()
                        ->where('stock', '<=', 5)
                        ->orderBy('stock')
                        ->take(5)
                        ->get()
                ),
            ],
        ]);
    }
}
