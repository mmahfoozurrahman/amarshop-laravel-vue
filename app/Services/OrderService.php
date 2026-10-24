<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private readonly CartService $cartService) {}

    /**
     * Create an order from the customer's cart.
     *
     * @param  array<string, mixed>  $shipping
     */
    public function placeOrder(User $user, array $shipping): Order
    {
        $cartItems = CartItem::with('product')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            abort(409, 'Your cart is empty.');
        }

        return DB::transaction(function () use ($user, $shipping, $cartItems) {
            $products = Product::whereIn('id', $cartItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotal = 0.0;

            foreach ($cartItems as $item) {
                $product = $products[$item->product_id] ?? null;

                if (! $product || ! $product->is_active) {
                    abort(409, 'One of the products in your cart is no longer available.');
                }

                if ($product->stock < $item->quantity) {
                    abort(409, "Only {$product->stock} item(s) of {$product->name} left in stock.");
                }

                $unitPrice = (float) $product->effective_price;
                $lineTotal = round($unitPrice * $item->quantity, 2);
                $subtotal += $lineTotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $shippingCost = $subtotal >= CartService::FREE_SHIPPING_THRESHOLD
                ? 0.0
                : (float) CartService::SHIPPING_FEE;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->nextOrderNumber(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping' => $shippingCost,
                'total' => round($subtotal + $shippingCost, 2),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => 'sslcommerz',
                'name' => $shipping['name'],
                'phone' => $shipping['phone'],
                'address' => $shipping['address'],
                'city' => $shipping['city'],
                'postcode' => $shipping['postcode'] ?? null,
                'note' => $shipping['note'] ?? null,
            ]);

            $order->items()->createMany($lines);

            foreach ($lines as $line) {
                Product::whereKey($line['product_id'])->decrement('stock', $line['quantity']);
            }

            $this->cartService->clear($user, null);

            return $order->load('items');
        });
    }

    /**
     * Mark an order as paid. Returns false when it was already paid.
     */
    public function markPaid(Order $order, ?string $transactionId = null, float|string|null $amount = null): bool
    {
        if ($order->isPaid()) {
            return false;
        }

        if ($amount !== null && round((float) $amount, 2) + 0.01 < round((float) $order->total, 2)) {
            return false;
        }

        $order->forceFill([
            'payment_status' => 'paid',
            'status' => $order->status === 'pending' ? 'processing' : $order->status,
            'transaction_id' => $transactionId ?: $order->transaction_id,
            'paid_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Cancel an unpaid order and put the reserved stock back (only once).
     */
    public function cancelAndRestoreStock(Order $order, string $paymentStatus = 'failed'): bool
    {
        return DB::transaction(function () use ($order, $paymentStatus) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->isPaid() || $locked->stock_restored_at !== null) {
                return false;
            }

            foreach ($locked->items as $item) {
                if ($item->product_id !== null) {
                    Product::whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $locked->forceFill([
                'status' => 'cancelled',
                'payment_status' => $paymentStatus,
                'cancelled_at' => now(),
                'stock_restored_at' => now(),
            ])->save();

            return true;
        });
    }

    public function nextOrderNumber(): string
    {
        $sequence = Order::whereDate('created_at', today())->count() + 1;

        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    public function generateTransactionId(): string
    {
        return 'DEMO-'.Str::upper(Str::random(12));
    }
}
