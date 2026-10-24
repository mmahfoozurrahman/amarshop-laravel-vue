<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ImageService;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));

        $products = Product::query()
            ->with(['brand', 'category'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term));
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->integer('brand_id')))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', true))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->safe()->except('thumbnail');
        $data['slug'] = Slugger::unique(Product::class, $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->images->storeUpload($request->file('thumbnail'), 'products');
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created.',
            'data' => new ProductDetailResource($product->load(['brand', 'category'])),
        ], 201);
    }

    public function show(Product $product): ProductDetailResource
    {
        return new ProductDetailResource($product->load(['brand', 'category']));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->safe()->except('thumbnail');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $product->is_active;
        $data['is_featured'] = $request->has('is_featured') ? $request->boolean('is_featured') : $product->is_featured;

        if ($product->name !== $data['name']) {
            $data['slug'] = Slugger::unique(Product::class, $data['name'], $product->id);
        }

        if ($request->hasFile('thumbnail')) {
            $this->images->deleteIfManaged($product->thumbnail);
            $data['thumbnail'] = $this->images->storeUpload($request->file('thumbnail'), 'products');
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated.',
            'data' => new ProductDetailResource($product->fresh()->load(['brand', 'category'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->images->deleteIfManaged($product->thumbnail);
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }
}
