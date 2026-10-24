<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use Tests\Feature\ApiTestCase;

class AdminTest extends ApiTestCase
{
    public function test_dashboard_returns_store_statistics(): void
    {
        $admin = $this->admin();
        $this->makeProduct();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats' => ['products', 'active_products', 'customers', 'orders', 'pending_orders', 'revenue'],
                    'recent_orders',
                    'low_stock',
                ],
            ]);
    }

    public function test_admin_can_manage_brands(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/brands', [
                'name' => 'Acme',
                'description' => 'Test brand',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'acme');

        $brandId = $created->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/brands/{$brandId}", ['name' => 'Acme Updated', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Updated')
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/brands', ['name' => 'Acme Updated'])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/brands/{$brandId}")
            ->assertOk();

        $this->assertDatabaseMissing('brands', ['id' => $brandId]);
    }

    public function test_admin_can_create_a_product_that_appears_in_the_storefront(): void
    {
        $admin = $this->admin();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/products', [
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'name' => 'Admin Created Product',
                'sku' => 'ADM-001',
                'price' => 1500,
                'stock' => 7,
                'is_featured' => true,
                'is_active' => true,
                'short_description' => 'Created through the admin API',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'admin-created-product')
            ->assertJsonPath('data.is_featured', true);

        $this->getJson('/api/products?q=Admin Created')->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_order_status_but_not_skip_transitions(): void
    {
        $admin = $this->admin();
        $order = Order::factory()->create([
            'user_id' => $this->customer()->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'processing');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertStatus(409);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$order->id}/status", ['status' => 'invalid'])
            ->assertStatus(422);
    }

    public function test_customer_list_excludes_admins_and_status_can_be_toggled(): void
    {
        $admin = $this->admin();
        $customer = $this->customer(['is_active' => true]);

        $list = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users')->assertOk();
        $this->assertCount(1, $list->json('data'));

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$customer->id}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_disabled_customer_cannot_login_with_otp(): void
    {
        $this->customer(['phone' => '01712345678', 'is_active' => false]);

        $otp = $this->postJson('/api/auth/request-otp', ['phone' => '01712345678'])->json('demo_otp');

        $this->postJson('/api/auth/verify-otp', [
            'phone' => '01712345678',
            'otp' => $otp,
        ])->assertStatus(403);
    }
}
