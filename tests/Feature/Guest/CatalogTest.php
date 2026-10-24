<?php

namespace Tests\Feature\Guest;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use Tests\Feature\ApiTestCase;

class CatalogTest extends ApiTestCase
{
    public function test_home_returns_all_storefront_sections_without_inactive_products(): void
    {
        Slider::factory()->count(2)->create();
        $featured = $this->makeProduct(['is_featured' => true, 'is_active' => true]);
        $this->makeProduct(['is_featured' => false, 'is_active' => true]);
        $this->makeProduct(['is_active' => false]);

        $response = $this->getJson('/api/home');

        $response->assertOk()->assertJsonStructure([
            'data' => ['sliders', 'brands', 'categories', 'latest_products', 'featured_products'],
        ]);

        $this->assertCount(2, $response->json('data.sliders'));
        $this->assertCount(2, $response->json('data.latest_products'));
        $this->assertCount(1, $response->json('data.featured_products'));
        $this->assertSame($featured->id, $response->json('data.featured_products.0.id'));
    }

    public function test_products_can_be_filtered_sorted_and_searched(): void
    {
        $electronics = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        $fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion']);
        $brand = Brand::factory()->create(['name' => 'TechNova', 'slug' => 'technova']);

        $cheap = Product::factory()->create([
            'category_id' => $electronics->id,
            'brand_id' => $brand->id,
            'name' => 'Cheap Charger',
            'price' => 500,
            'sale_price' => null,
            'is_featured' => false,
        ]);

        $expensive = Product::factory()->create([
            'category_id' => $electronics->id,
            'brand_id' => $brand->id,
            'name' => 'Luxury Laptop',
            'price' => 90000,
            'sale_price' => null,
            'is_featured' => false,
        ]);

        Product::factory()->create(['category_id' => $fashion->id, 'name' => 'Cotton Shirt', 'is_featured' => false]);
        Product::factory()->create(['category_id' => $electronics->id, 'name' => 'Hidden Item', 'is_active' => false, 'is_featured' => false]);

        $response = $this->getJson('/api/products?category=electronics&sort=price_asc');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame($cheap->id, $response->json('data.0.id'));
        $this->assertSame($expensive->id, $response->json('data.1.id'));

        $this->getJson('/api/products?brand=technova')->assertJsonCount(2, 'data');
        $this->getJson('/api/products?q=Charger')->assertJsonCount(1, 'data');
        $this->getJson('/api/products?min_price=1000&max_price=2000&category=electronics')->assertJsonCount(0, 'data');
        $this->getJson('/api/products?featured=1')->assertJsonCount(0, 'data');
    }

    public function test_product_detail_includes_description_and_related_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'description' => 'A very detailed description.',
        ]);
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products/'.$product->slug);

        $response->assertOk()
            ->assertJsonPath('data.product.slug', $product->slug)
            ->assertJsonPath('data.product.description', 'A very detailed description.');

        $this->assertCount(2, $response->json('data.related'));
    }

    public function test_inactive_products_are_hidden_from_the_public_api(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->getJson('/api/products/'.$product->slug)->assertNotFound();
        $this->getJson('/api/products')->assertJsonCount(0, 'data');
    }

    public function test_products_can_be_browsed_by_brand_and_category(): void
    {
        $brand = Brand::factory()->create(['name' => 'TechNova', 'slug' => 'technova']);
        $category = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        Product::factory()->count(2)->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $brandResponse = $this->getJson('/api/brands/technova')->assertOk();
        $this->assertSame('TechNova', $brandResponse->json('brand.name'));
        $this->assertCount(2, $brandResponse->json('data'));

        $categoryResponse = $this->getJson('/api/categories/electronics')->assertOk();
        $this->assertSame('Electronics', $categoryResponse->json('category.name'));
        $this->assertCount(2, $categoryResponse->json('data'));
    }

    public function test_inactive_brand_is_not_public(): void
    {
        Brand::factory()->create(['slug' => 'hidden-brand', 'is_active' => false]);

        $this->getJson('/api/brands/hidden-brand')->assertNotFound();
    }
}
