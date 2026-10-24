<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    private const FILTERS = ['q', 'category', 'brand', 'featured', 'min_price', 'max_price', 'sort'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 12), 48));

        $products = Product::query()
            ->with(['brand', 'category'])
            ->active()
            ->filter($request->only(self::FILTERS))
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['brand', 'category'])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with(['brand', 'category'])
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return response()->json([
            'data' => [
                'product' => new ProductDetailResource($product),
                'related' => ProductResource::collection($related),
            ],
        ]);
    }
}
