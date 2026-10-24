<?php

namespace Tests\Feature\User;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Tests\Feature\ApiTestCase;

class CheckoutTest extends ApiTestCase
{
    private function addToCart($customer, Product $product, int $quantity = 1): void
    {
        CartItem::create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_checkout_requires_an_authenticated_customer(): void
    {
        $this->postJson('/api/checkout', $this->shippingPayload())->assertStatus(401);
    }

    public function test_checkout_fails_when_the_cart_is_empty(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->assertStatus(409);
    }

    public function test_shipping_details_are_validated(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['stock' => 5]);
        $this->addToCart($customer, $product);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', ['name' => '', 'phone' => 'bad', 'address' => '', 'city' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'address', 'city']);
    }

    public function test_order_is_created_with_server_side_totals_and_stock_is_reserved(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 500, 'sale_price' => null, 'stock' => 10]);
        $this->addToCart($customer, $product, 2);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload());

        $response->assertCreated()
            ->assertJsonPath('gateway', 'demo')
            ->assertJsonPath('redirect_url', '/payment/demo/'.$response->json('data.order_number'))
            ->assertJsonPath('data.subtotal', 1000)
            ->assertJsonPath('data.shipping', 0)
            ->assertJsonPath('data.total', 1000);

        $order = Order::where('user_id', $customer->id)->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(0, CartItem::where('user_id', $customer->id)->count());
        $this->assertSame($product->name, $order->items()->first()->product_name);
    }

    public function test_paying_an_unpaid_order_returns_the_relative_demo_gateway_path(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 1200, 'sale_price' => null, 'stock' => 5]);
        $this->addToCart($customer, $product);

        $orderNumber = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->json('data.order_number');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/orders/{$orderNumber}/pay")
            ->assertOk()
            ->assertJsonPath('gateway', 'demo')
            ->assertJsonPath('redirect_url', "/payment/demo/{$orderNumber}");

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/success")
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/orders/{$orderNumber}/pay")
            ->assertStatus(409);
    }

    public function test_shipping_fee_applies_below_the_free_shipping_threshold(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 500, 'sale_price' => null, 'stock' => 5]);
        $this->addToCart($customer, $product);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload());

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 500)
            ->assertJsonPath('data.shipping', 80)
            ->assertJsonPath('data.total', 580);
    }

    public function test_checkout_blocks_when_stock_changed_since_the_cart_was_filled(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 500, 'stock' => 1]);
        $this->addToCart($customer, $product, 3);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->assertStatus(409);

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_demo_payment_success_marks_the_order_as_paid(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 1200, 'sale_price' => null, 'stock' => 5]);
        $this->addToCart($customer, $product);

        $orderNumber = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->json('data.order_number');

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/success");

        $response->assertOk()->assertJsonPath('data.payment_status', 'paid');

        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->assertSame('processing', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->transaction_id);

        // Calling the callback again must be idempotent.
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/success")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_cancelling_payment_restores_stock_only_once(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct(['price' => 900, 'sale_price' => null, 'stock' => 10]);
        $this->addToCart($customer, $product, 2);

        $orderNumber = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->json('data.order_number');

        $this->assertSame(8, $product->fresh()->stock);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(10, $product->fresh()->stock);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/cancel")
            ->assertOk();

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_customers_cannot_pay_for_someone_elses_order(): void
    {
        $owner = $this->customer();
        $intruder = $this->customer();
        $product = $this->makeProduct(['stock' => 5]);
        $this->addToCart($owner, $product);

        $orderNumber = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->json('data.order_number');

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/payment/demo/{$orderNumber}/success")
            ->assertStatus(403);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/orders/{$orderNumber}")
            ->assertNotFound();
    }

    public function test_order_history_only_contains_the_customers_own_orders(): void
    {
        $customer = $this->customer();
        $other = $this->customer();
        $product = $this->makeProduct(['price' => 300, 'stock' => 10]);
        $this->addToCart($customer, $product);

        $orderNumber = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/checkout', $this->shippingPayload())
            ->json('data.order_number');

        Order::factory()->create(['user_id' => $other->id]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', $orderNumber);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/orders/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('data.shipping_address.city', 'Dhaka');
    }
}
