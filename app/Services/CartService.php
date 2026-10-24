<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const MAX_QUANTITY = 20;

    public const FREE_SHIPPING_THRESHOLD = 1000;

    public const SHIPPING_FEE = 80;

    /**
     * @return Collection<int, CartItem>
     */
    public function items(?User $user, ?string $guestToken): Collection
    {
        return CartItem::query()
            ->with(['product.brand', 'product.category'])
            ->forOwner($user?->id, $guestToken)
            ->latest('id')
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null)
            ->values();
    }

    public function add(?User $user, ?string $guestToken, int $productId, int $quantity = 1): CartItem
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            abort(404, 'Product not found.');
        }

        $existing = CartItem::query()
            ->forOwner($user?->id, $guestToken)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = ($existing?->quantity ?? 0) + $quantity;
        $this->assertQuantityAllowed($product, $newQuantity);

        if ($existing) {
            $existing->update(['quantity' => $newQuantity]);

            return $existing->fresh('product');
        }

        return CartItem::create([
            'user_id' => $user?->id,
            'guest_token' => $user ? null : $guestToken,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ])->load('product');
    }

    public function update(?User $user, ?string $guestToken, Product $product, int $quantity): CartItem
    {
        $item = CartItem::query()
            ->forOwner($user?->id, $guestToken)
            ->where('product_id', $product->id)
            ->first();

        if (! $item) {
            abort(404, 'This product is not in your cart.');
        }

        $this->assertQuantityAllowed($product, $quantity);

        $item->update(['quantity' => $quantity]);

        return $item->fresh('product');
    }

    public function remove(?User $user, ?string $guestToken, Product $product): void
    {
        $deleted = CartItem::query()
            ->forOwner($user?->id, $guestToken)
            ->where('product_id', $product->id)
            ->delete();

        if ($deleted === 0) {
            abort(404, 'This product is not in your cart.');
        }
    }

    public function clear(?User $user, ?string $guestToken): void
    {
        CartItem::query()->forOwner($user?->id, $guestToken)->delete();
    }

    /**
     * Merge a guest cart into the authenticated customer's cart.
     */
    public function mergeGuestCart(User $user, ?string $guestToken): void
    {
        if (! $guestToken) {
            return;
        }

        $guestItems = CartItem::with('product')->where('guest_token', $guestToken)->get();

        foreach ($guestItems as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active) {
                $item->delete();

                continue;
            }

            $cap = min($product->stock, self::MAX_QUANTITY);

            if ($cap < 1) {
                $item->delete();

                continue;
            }

            $existing = CartItem::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => min($existing->quantity + $item->quantity, $cap)]);
                $item->delete();
            } else {
                $item->forceFill(['user_id' => $user->id, 'guest_token' => null])->save();
            }
        }
    }

    /**
     * Cart payload shared by the guest and customer cart endpoints.
     *
     * @return array<string, mixed>
     */
    public function summary(?User $user, ?string $guestToken): array
    {
        $items = $this->items($user, $guestToken);
        $lines = [];
        $subtotal = 0.0;
        $itemCount = 0;

        foreach ($items as $item) {
            $product = $item->product;
            $unitPrice = (float) $product->effective_price;
            $lineTotal = round($unitPrice * $item->quantity, 2);
            $subtotal += $lineTotal;
            $itemCount += $item->quantity;

            $lines[] = [
                'id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumbnail' => $product->thumbnail_url,
                'brand' => $product->brand?->name,
                'price' => (float) $product->price,
                'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
                'effective_price' => $unitPrice,
                'quantity' => $item->quantity,
                'stock' => $product->stock,
                'max_quantity' => min($product->stock, self::MAX_QUANTITY),
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $shipping = $subtotal > 0 && $subtotal < self::FREE_SHIPPING_THRESHOLD
            ? (float) self::SHIPPING_FEE
            : 0.0;

        return [
            'items' => $lines,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => round($subtotal + $shipping, 2),
            'free_shipping_threshold' => self::FREE_SHIPPING_THRESHOLD,
        ];
    }

    private function assertQuantityAllowed(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must be at least 1.',
            ]);
        }

        if ($product->stock < 1) {
            abort(409, 'This product is out of stock.');
        }

        if ($quantity > $product->stock) {
            abort(409, "Only {$product->stock} item(s) left in stock.");
        }

        if ($quantity > self::MAX_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => 'You can add at most '.self::MAX_QUANTITY.' units of this product.',
            ]);
        }
    }
}
