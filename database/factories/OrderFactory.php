<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 500, 25000);
        $shipping = $subtotal >= 1000 ? 0 : 80;

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'sslcommerz',
            'name' => fake()->name(),
            'phone' => fake()->numerify('017########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Dhaka', 'Chattogram', 'Sylhet', 'Khulna']),
            'postcode' => fake()->numerify('####'),
            'note' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'payment_status' => 'paid',
            'transaction_id' => 'TRX'.Str::upper(Str::random(10)),
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'failed',
            'cancelled_at' => now(),
            'stock_restored_at' => now(),
        ]);
    }
}
