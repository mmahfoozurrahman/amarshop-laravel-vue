<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $request->user()->wishlists()
            ->with(['product.brand', 'product.category'])
            ->latest('id')
            ->get();

        return WishlistResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::active()->find($validated['product_id']);

        if (! $product) {
            abort(404, 'Product not found.');
        }

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'message' => $wishlist->wasRecentlyCreated
                ? 'Added to your wishlist.'
                : 'This product is already in your wishlist.',
            'data' => new WishlistResource($wishlist->load('product.brand', 'product.category')),
        ], $wishlist->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $deleted = $request->user()->wishlists()
            ->where('product_id', $product->id)
            ->delete();

        if ($deleted === 0) {
            abort(404, 'This product is not in your wishlist.');
        }

        return response()->json(['message' => 'Removed from your wishlist.']);
    }
}
