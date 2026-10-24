<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\ImageService;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));

        $brands = Brand::query()
            ->withCount('products')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return BrandResource::collection($brands);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $data = $request->safe()->except('logo');
        $data['slug'] = Slugger::unique(Brand::class, $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->images->storeUpload($request->file('logo'), 'brands');
        }

        $brand = Brand::create($data);

        return response()->json([
            'message' => 'Brand created.',
            'data' => new BrandResource($brand->loadCount('products')),
        ], 201);
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand->loadCount('products'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $data = $request->safe()->except('logo');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $brand->is_active;

        if ($brand->name !== $data['name']) {
            $data['slug'] = Slugger::unique(Brand::class, $data['name'], $brand->id);
        }

        if ($request->hasFile('logo')) {
            $this->images->deleteIfManaged($brand->logo);
            $data['logo'] = $this->images->storeUpload($request->file('logo'), 'brands');
        }

        $brand->update($data);

        return response()->json([
            'message' => 'Brand updated.',
            'data' => new BrandResource($brand->fresh()->loadCount('products')),
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->images->deleteIfManaged($brand->logo);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted.']);
    }
}
