<?php

declare(strict_types=1);

namespace Modules\Marketplace\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;
use Modules\Marketplace\Models\Vendor;
use Tests\TestCase;

class MarketplaceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'phone' => '+963' . fake()->unique()->numerify('#########'),
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    public function test_can_list_categories(): void
    {
        $user = $this->createUser();

        ProductCategory::create([
            'name' => 'E-Books',
            'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books',
            'sort_order' => 1,
        ]);

        ProductCategory::create([
            'name' => 'Software',
            'name_ar' => 'برمجيات',
            'slug' => 'software',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/marketplace/categories');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_product(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'E-Books',
            'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books',
            'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop',
            'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/products', [
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 5000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Product');
    }

    public function test_can_create_and_place_order(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'E-Books', 'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Digital Book', 'price' => 3000,
        ]);

        $createResponse = $this->actingAs($user)->postJson('/api/v1/marketplace/orders', [
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $createResponse->assertCreated();
        $orderId = $createResponse->json('data.id');

        $placeResponse = $this->actingAs($user)->postJson("/api/v1/marketplace/orders/{$orderId}/place");

        $placeResponse->assertOk()
            ->assertJsonPath('data.status', 'paid');
    }

    public function test_can_fulfill_order(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'E-Books', 'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Digital Book', 'price' => 3000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-TEST-001',
            'total_amount' => 6000,
            'net_amount' => 6000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Digital Book',
            'quantity' => 2,
            'unit_price' => 3000,
            'total_price' => 6000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/marketplace/orders/{$order->id}/fulfill");

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_can_refund_order(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'E-Books', 'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Digital Book', 'price' => 3000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-TEST-002',
            'total_amount' => 6000,
            'net_amount' => 6000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Digital Book',
            'quantity' => 2,
            'unit_price' => 3000,
            'total_price' => 6000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/marketplace/orders/{$order->id}/refund");

        $response->assertOk()
            ->assertJsonPath('data.status', 'refunded');
    }

    public function test_can_approve_vendor(): void
    {
        $user = $this->createUser();

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Pending Shop',
            'phone' => '0987654321',
            'governorate' => 'Aleppo',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/marketplace/vendors/{$vendor->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }
}
