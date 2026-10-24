<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('phone', '01700000000')->first();

        if (! $user) {
            return;
        }

        $paidProducts = Product::whereIn('slug', [
            'novabook-air-14-inch-laptop',
            'pulsebuds-pro-wireless-earbuds',
        ])->get();

        $pendingProducts = Product::whereIn('slug', [
            'nordic-table-lamp',
        ])->get();

        $this->createOrder(
            user: $user,
            products: $paidProducts,
            quantities: [1, 2],
            status: 'completed',
            paymentStatus: 'paid',
            placedAt: now()->subDays(12),
            orderSuffix: '0001',
            shipping: ['Rahim Uddin', '01700000000', 'House 12, Road 5, Dhanmondi', 'Dhaka', '1209'],
        );

        $this->createOrder(
            user: $user,
            products: $pendingProducts,
            quantities: [1],
            status: 'pending',
            paymentStatus: 'unpaid',
            placedAt: now()->subDays(2),
            orderSuffix: '0002',
            shipping: ['Rahim Uddin', '01700000000', 'House 12, Road 5, Dhanmondi', 'Dhaka', '1209'],
        );
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  array<int, int>  $quantities
     * @param  array<int, string>  $shipping
     */
    private function createOrder(
        User $user,
        $products,
        array $quantities,
        string $status,
        string $paymentStatus,
        \DateTimeInterface $placedAt,
        string $orderSuffix,
        array $shipping,
    ): void {
        if ($products->isEmpty()) {
            return;
        }

        $orderNumber = 'ORD-'.$placedAt->format('Ymd').'-'.$orderSuffix;

        if (Order::where('order_number', $orderNumber)->exists()) {
            return;
        }

        $subtotal = 0.0;
        $lines = [];

        foreach ($products->values() as $index => $product) {
            $quantity = $quantities[$index] ?? 1;
            $unitPrice = (float) $product->effective_price;
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ];
        }

        $shippingCost = $subtotal >= 1000 ? 0.0 : 80.0;

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => $shippingCost,
            'total' => $subtotal + $shippingCost,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => 'sslcommerz',
            'transaction_id' => $paymentStatus === 'paid' ? 'DEMO-'.Str::upper(Str::random(10)) : null,
            'name' => $shipping[0],
            'phone' => $shipping[1],
            'address' => $shipping[2],
            'city' => $shipping[3],
            'postcode' => $shipping[4],
            'paid_at' => $paymentStatus === 'paid' ? $placedAt->format('Y-m-d H:i:s') : null,
        ]);

        $order->forceFill(['created_at' => $placedAt, 'updated_at' => $placedAt])->save();

        $order->items()->createMany($lines);

        if ($paymentStatus === 'paid') {
            foreach ($lines as $line) {
                Product::whereKey($line['product_id'])->decrement('stock', $line['quantity']);
            }
        }
    }
}
