<?php

namespace App\Http\Controllers\Guest;

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
            'data' => $this->cart->summary(null, $this->guestToken($request)),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $this->cart->add(
            null,
            $this->guestToken($request),
            $request->integer('product_id'),
            $request->integer('quantity', 1) ?: 1,
        );

        return response()->json([
            'message' => 'Product added to your cart.',
            'data' => $this->cart->summary(null, $this->guestToken($request)),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, Product $product): JsonResponse
    {
        $this->cart->update(null, $this->guestToken($request), $product, $request->integer('quantity'));

        return response()->json([
            'message' => 'Cart updated.',
            'data' => $this->cart->summary(null, $this->guestToken($request)),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->cart->remove(null, $this->guestToken($request), $product);

        return response()->json([
            'message' => 'Product removed from your cart.',
            'data' => $this->cart->summary(null, $this->guestToken($request)),
        ]);
    }

    private function guestToken(Request $request): ?string
    {
        return $request->attributes->get('guest_token');
    }
}
