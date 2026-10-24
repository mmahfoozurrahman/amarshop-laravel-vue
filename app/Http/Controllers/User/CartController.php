<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->cart->summary($request->user(), null),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $this->cart->add(
            $request->user(),
            null,
            $request->integer('product_id'),
            $request->integer('quantity', 1) ?: 1,
        );

        return response()->json([
            'message' => 'Product added to your cart.',
            'data' => $this->cart->summary($request->user(), null),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse
    {
        $this->cart->update($request->user(), null, $product, $request->integer('quantity'));

        return response()->json([
            'message' => 'Cart updated.',
            'data' => $this->cart->summary($request->user(), null),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->cart->remove($request->user(), null, $product);

        return response()->json([
            'message' => 'Product removed from your cart.',
            'data' => $this->cart->summary($request->user(), null),
        ]);
    }

    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_token' => ['required', 'string', 'max:64'],
        ]);

        $this->cart->mergeGuestCart($request->user(), $validated['guest_token']);

        return response()->json([
            'message' => 'Your guest cart was merged.',
            'data' => $this->cart->summary($request->user(), null),
        ]);
    }
}
