<?php

namespace Tests\Feature\Guest;

use App\Models\CartItem;
use Tests\Feature\ApiTestCase;

class CartTest extends ApiTestCase
{
    public function test_guest_can_add_and_review_a_cart(): void
    {
        $product = $this->makeProduct(['price' => 500, 'sale_price' => null, 'stock' => 10]);

        $response = $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertCreated()
            ->assertJsonPath('data.item_count', 2);

        $this->assertEquals(1000, $response->json('data.subtotal'));
        $this->assertEquals(0, $response->json('data.shipping'));
        $this->assertEquals(1000, $response->json('data.total'));

        $this->withHeaders($this->guestHeaders())
            ->getJson('/api/guest/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 2);

        $this->assertDatabaseHas('cart_items', [
            'guest_token' => $this->guestToken(),
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_guest_cart_requires_a_valid_token(): void
    {
        $this->getJson('/api/guest/cart')->assertStatus(422);

        $this->withHeaders(['X-Guest-Token' => 'short'])
            ->getJson('/api/guest/cart')
            ->assertStatus(422);
    }

    public function test_adding_the_same_product_increments_quantity(): void
    {
        $product = $this->makeProduct(['stock' => 10, 'price' => 300]);

        $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertCreated()->assertJsonPath('data.item_count', 3);

        $this->assertSame(1, CartItem::where('guest_token', $this->guestToken())->count());
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        $product = $this->makeProduct(['stock' => 2]);

        $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(409);
    }

    public function test_guest_can_update_and_remove_an_item(): void
    {
        $product = $this->makeProduct(['stock' => 10, 'price' => 250]);

        $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 1]);

        $this->withHeaders($this->guestHeaders())
            ->patchJson("/api/guest/cart/{$product->id}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.item_count', 4);

        $this->withHeaders($this->guestHeaders())
            ->deleteJson("/api/guest/cart/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.item_count', 0);

        $this->assertDatabaseMissing('cart_items', ['guest_token' => $this->guestToken()]);
    }

    public function test_updating_a_missing_cart_item_returns_404(): void
    {
        $product = $this->makeProduct();

        $this->withHeaders($this->guestHeaders())
            ->patchJson("/api/guest/cart/{$product->id}", ['quantity' => 2])
            ->assertNotFound();
    }

    public function test_guest_cart_is_merged_into_the_customer_cart_on_login(): void
    {
        $product = $this->makeProduct(['stock' => 10, 'price' => 400]);

        $this->withHeaders($this->guestHeaders())
            ->postJson('/api/guest/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated();

        $otp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '01712345678',
            'otp' => $otp,
            'guest_token' => $this->guestToken(),
        ]);

        $response->assertOk()->assertJsonPath('cart.item_count', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'guest_token' => null,
        ]);

        $this->assertDatabaseMissing('cart_items', ['guest_token' => $this->guestToken()]);
    }

    public function test_customer_cart_requires_authentication(): void
    {
        $this->getJson('/api/cart')->assertStatus(401);
    }

    public function test_customer_cart_is_isolated_per_user(): void
    {
        $product = $this->makeProduct(['stock' => 10]);
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated();

        $other = $this->customer();
        $this->actingAs($other, 'sanctum')
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 0);
    }
}
