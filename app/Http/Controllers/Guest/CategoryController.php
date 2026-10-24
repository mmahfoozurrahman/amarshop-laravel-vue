<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Request $request, string $slug): AnonymousResourceCollection
    {
        $category = Category::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = max(1, min($request->integer('per_page', 12), 48));

        $products = Product::with(['brand', 'category'])
            ->active()
            ->where('category_id', $category->id)
            ->filter($request->only(['q', 'brand', 'min_price', 'max_price', 'sort']))
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products)->additional([
            'category' => (new CategoryResource($category))->resolve(),
        ]);
    }
}
