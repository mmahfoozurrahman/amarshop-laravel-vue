<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ImageService;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));

        $categories = Category::query()
            ->withCount('products')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->input('q').'%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['slug'] = Slugger::unique(Category::class, $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->storeUpload($request->file('image'), 'categories');
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created.',
            'data' => new CategoryResource($category->loadCount('products')),
        ], 201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $category->is_active;

        if ($category->name !== $data['name']) {
            $data['slug'] = Slugger::unique(Category::class, $data['name'], $category->id);
        }

        if ($request->hasFile('image')) {
            $this->images->deleteIfManaged($category->image);
            $data['image'] = $this->images->storeUpload($request->file('image'), 'categories');
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated.',
            'data' => new CategoryResource($category->fresh()->loadCount('products')),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->images->deleteIfManaged($category->image);
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
