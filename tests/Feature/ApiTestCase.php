<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function customer(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function makeProduct(array $attributes = []): Product
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        return Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            ...$attributes,
        ]);
    }

    protected function guestToken(): string
    {
        return '11111111-2222-4333-8444-555555555555';
    }

    protected function guestHeaders(): array
    {
        return ['X-Guest-Token' => $this->guestToken()];
    }

    protected function shippingPayload(array $overrides = []): array
    {
        return [
            'name' => 'Test Customer',
            'phone' => '01712345678',
            'address' => 'House 1, Road 2, Banani',
            'city' => 'Dhaka',
            'postcode' => '1213',
            ...$overrides,
        ];
    }

    protected function assertValidationError(TestResponse $response, string $field): void
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($field);
    }
}
