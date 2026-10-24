<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSliderRequest;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SliderController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sliders = Slider::query()
            ->ordered()
            ->paginate(max(1, min($request->integer('per_page', 20), 50)));

        return SliderResource::collection($sliders);
    }

    public function store(StoreSliderRequest $request): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $request->integer('sort_order', 0);

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->storeUpload($request->file('image'), 'sliders');
        }

        $slider = Slider::create($data);

        return response()->json([
            'message' => 'Slider created.',
            'data' => new SliderResource($slider),
        ], 201);
    }

    public function show(Slider $slider): SliderResource
    {
        return new SliderResource($slider);
    }

    public function update(UpdateSliderRequest $request, Slider $slider): JsonResponse
    {
        $data = $request->safe()->except('image');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $slider->is_active;
        $data['sort_order'] = $request->has('sort_order') ? $request->integer('sort_order') : $slider->sort_order;

        if ($request->hasFile('image')) {
            $this->images->deleteIfManaged($slider->image);
            $data['image'] = $this->images->storeUpload($request->file('image'), 'sliders');
        }

        $slider->update($data);

        return response()->json([
            'message' => 'Slider updated.',
            'data' => new SliderResource($slider->fresh()),
        ]);
    }

    public function destroy(Slider $slider): JsonResponse
    {
        $this->images->deleteIfManaged($slider->image);
        $slider->delete();

        return response()->json(['message' => 'Slider deleted.']);
    }
}
