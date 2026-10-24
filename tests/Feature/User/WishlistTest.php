<?php

namespace Tests\Feature\User;

use Tests\Feature\ApiTestCase;

class WishlistTest extends ApiTestCase
{
    public function test_guests_cannot_use_the_wishlist(): void
    {
        $product = $this->makeProduct();

        $this->getJson('/api/wishlist')->assertStatus(401);
        $this->postJson('/api/wishlist', ['product_id' => $product->id])->assertStatus(401);
    }

    public function test_customer_can_add_a_product_only_once(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => $product->id])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('message', 'This product is already in your wishlist.');

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/wishlist')
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseCount('wishlists', 1);
    }

    public function test_customer_can_remove_a_wishlist_item(): void
    {
        $customer = $this->customer();
        $product = $this->makeProduct();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => $product->id]);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/wishlist/{$product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('wishlists', ['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/wishlist/{$product->id}")
            ->assertNotFound();
    }

    public function test_wishlists_are_private_to_each_customer(): void
    {
        $product = $this->makeProduct();
        $owner = $this->customer();
        $other = $this->customer();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => $product->id]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/wishlist/{$product->id}")
            ->assertNotFound();
    }

    public function test_unknown_product_is_rejected(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => 99999])
            ->assertStatus(422);
    }
}
