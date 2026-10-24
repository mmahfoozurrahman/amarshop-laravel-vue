<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SliderResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $sliders = Slider::active()->ordered()->get();

        $brands = Brand::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $categories = Category::active()
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $latest = Product::with(['brand', 'category'])
            ->active()
            ->latest()
            ->take(8)
            ->get();

        $featured = Product::with(['brand', 'category'])
            ->active()
            ->featured()
            ->latest()
            ->take(8)
            ->get();

        return response()->json([
            'data' => [
                'sliders' => SliderResource::collection($sliders),
                'brands' => BrandResource::collection($brands),
                'categories' => CategoryResource::collection($categories),
                'latest_products' => ProductResource::collection($latest),
                'featured_products' => ProductResource::collection($featured),
            ],
        ]);
    }
}
