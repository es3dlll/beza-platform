<?php

declare(strict_types=1);

namespace Modules\Marketplace\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;
use Modules\Marketplace\Models\Shipment;
use Modules\Marketplace\Models\ShippingZone;
use Modules\Marketplace\Models\Vendor;
use Tests\TestCase;

final class MarketplaceM3M4FeatureTest extends TestCase
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

    public function test_can_calculate_shipping_fee(): void
    {
        $user = $this->createUser();

        ShippingZone::create([
            'name' => 'Zone 1',
            'name_ar' => 'المنطقة 1',
            'governorates' => ['Damascus', 'Rural Damascus'],
            'base_fee' => 5000,
            'per_kg_fee' => 1000,
            'estimated_days' => 2,
        ]);

        $response = $this->actingAs($user)->postJson('/api/shipping/calculate', [
            'governorate' => 'Damascus',
            'weight_grams' => 2500,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.fee', 5000 + (int) ceil(2500 / 1000) * 1000);
    }

    public function test_can_create_shipment(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'Physical Goods', 'name_ar' => 'سلع مادية',
            'slug' => 'physical-goods', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Physical Item', 'price' => 10000,
            'product_type' => 'physical',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-SHIP-001',
            'total_amount' => 10000,
            'net_amount' => 10000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Physical Item',
            'quantity' => 1,
            'unit_price' => 10000,
            'total_price' => 10000,
        ]);

        $response = $this->actingAs($user)->postJson('/api/shipping/create', [
            'order_id' => $order->id,
            'shipping_address' => '123 Main St, Damascus',
            'governorate' => 'Damascus',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '0987654321',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.governorate', 'Damascus')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_can_track_shipment(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'Physical Goods', 'name_ar' => 'سلع مادية',
            'slug' => 'physical-goods', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Physical Item', 'price' => 10000,
            'product_type' => 'physical',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-TRK-001',
            'total_amount' => 10000,
            'net_amount' => 10000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $trackingNumber = 'TRK-' . fake()->unique()->numerify('########');

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber,
            'shipping_address' => '456 Oak Ave, Aleppo',
            'governorate' => 'Aleppo',
            'recipient_name' => 'Jane Doe',
            'recipient_phone' => '0987654321',
            'status' => 'in_transit',
        ]);

        $response = $this->actingAs($user)->getJson('/api/shipping/track?tracking_number=' . $trackingNumber);

        $response->assertOk()
            ->assertJsonPath('data.tracking_number', $trackingNumber)
            ->assertJsonPath('data.status', 'in_transit');
    }

    public function test_can_operate_cod_lifecycle(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'Physical Goods', 'name_ar' => 'سلع مادية',
            'slug' => 'physical-goods', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'COD Item', 'price' => 15000,
            'product_type' => 'physical',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-COD-001',
            'total_amount' => 15000,
            'net_amount' => 15000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'shipping_address' => '789 Pine Rd, Homs',
            'governorate' => 'Homs',
            'recipient_name' => 'COD Agent',
            'recipient_phone' => '0987654321',
            'status' => 'out_for_delivery',
        ]);

        $agent = $this->createUser();

        $collectResponse = $this->actingAs($user)->postJson('/api/cod/collect', [
            'shipment_id' => $shipment->id,
            'agent_id' => $agent->id,
        ]);

        $collectResponse->assertCreated()
            ->assertJsonPath('data.status', 'collected');

        $collectionId = $collectResponse->json('data.id');

        $remitResponse = $this->actingAs($user)->postJson("/api/cod/remit/{$collectionId}");

        $remitResponse->assertOk()
            ->assertJsonPath('data.status', 'remitted');
    }

    public function test_can_list_shipping_zones(): void
    {
        $user = $this->createUser();

        ShippingZone::create([
            'name' => 'Zone 1',
            'name_ar' => 'المنطقة 1',
            'governorates' => ['Damascus'],
            'base_fee' => 5000,
            'per_kg_fee' => 1000,
            'estimated_days' => 2,
        ]);

        ShippingZone::create([
            'name' => 'Zone 2',
            'name_ar' => 'المنطقة 2',
            'governorates' => ['Aleppo'],
            'base_fee' => 7000,
            'per_kg_fee' => 1500,
            'estimated_days' => 3,
        ]);

        $response = $this->actingAs($user)->getJson('/api/shipping/zones');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_list_products_via_api(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'API Products', 'name_ar' => 'منتجات API',
            'slug' => 'api-products', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'API Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'API Product 1', 'price' => 5000,
        ]);

        Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'API Product 2', 'price' => 8000,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_order_via_api(): void
    {
        $user = $this->createUser();

        $category = ProductCategory::create([
            'name' => 'API Products', 'name_ar' => 'منتجات API',
            'slug' => 'api-products', 'sort_order' => 1,
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'API Shop', 'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'API Order Product', 'price' => 12000,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/orders', [
            'vendor_id' => $vendor->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'cart')
            ->assertJsonPath('data.total_amount', 36000);
    }

    public function test_rate_limit_on_api(): void
    {
        $user = $this->createUser();

        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->getJson('/api/v1/products');
            $response->assertStatus(200);
        }

        $response = $this->actingAs($user)->getJson('/api/v1/products');
        $response->assertStatus(429);
    }
}
