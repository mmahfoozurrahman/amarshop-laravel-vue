<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return BrandResource::collection($brands);
    }

    public function show(Request $request, string $slug): AnonymousResourceCollection
    {
        $brand = Brand::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = max(1, min($request->integer('per_page', 12), 48));

        $products = Product::with(['brand', 'category'])
            ->active()
            ->where('brand_id', $brand->id)
            ->filter($request->only(['q', 'min_price', 'max_price', 'sort']))
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products)->additional([
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }
}
