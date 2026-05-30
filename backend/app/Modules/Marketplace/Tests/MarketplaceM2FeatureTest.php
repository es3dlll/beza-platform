<?php

declare(strict_types=1);

namespace Modules\Marketplace\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\GiftCard;
use Modules\Marketplace\Models\Order;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;
use Modules\Marketplace\Models\PromoCode;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Services\LoyaltyService;
use Tests\TestCase;

final class MarketplaceM2FeatureTest extends TestCase
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

    private function createVendor(User $user): Vendor
    {
        return Vendor::create([
            'user_id' => $user->id,
            'shop_name' => 'Test Shop',
            'phone' => '1234567890',
            'governorate' => 'Damascus',
        ]);
    }

    private function createCategory(): ProductCategory
    {
        return ProductCategory::create([
            'name' => 'E-Books', 'name_ar' => 'كتب إلكترونية',
            'slug' => 'e-books', 'sort_order' => 1,
        ]);
    }

    public function test_can_purchase_gift_card(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $vendor = $this->createVendor($user);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Digital Book', 'price' => 5000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-GC-001',
            'total_amount' => 5000,
            'net_amount' => 5000,
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Digital Book',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_price' => 5000,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/gift-cards/purchase', [
            'order_id' => $order->id,
            'cards' => [
                [
                    'amount' => 2000,
                    'recipient_phone' => '+963911111111',
                    'message' => 'Happy Birthday!',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_deliver_gift_card(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor($user);

        $giftCard = GiftCard::create([
            'order_id' => (new Order)->create([
                'user_id' => $user->id, 'vendor_id' => $vendor->id,
                'order_number' => 'ORD-GC-002', 'total_amount' => 2000,
                'net_amount' => 2000, 'status' => 'paid',
            ])->id,
            'vendor_id' => $vendor->id,
            'amount' => 2000,
            'balance' => 2000,
            'code' => 'GC-DEL-TEST',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/marketplace/gift-cards/{$giftCard->id}/deliver", [
            'method' => 'sms',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    public function test_can_redeem_gift_card(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor($user);

        GiftCard::create([
            'order_id' => (new Order)->create([
                'user_id' => $user->id, 'vendor_id' => $vendor->id,
                'order_number' => 'ORD-GC-003', 'total_amount' => 3000,
                'net_amount' => 3000, 'status' => 'paid',
            ])->id,
            'vendor_id' => $vendor->id,
            'amount' => 3000,
            'balance' => 3000,
            'code' => 'GC-RED-TEST',
            'status' => 'delivered',
            'delivered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/gift-cards/redeem', [
            'code' => 'GC-RED-TEST',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 3000);
    }

    public function test_can_check_balance(): void
    {
        $user = $this->createUser();
        $vendor = $this->createVendor($user);

        GiftCard::create([
            'order_id' => (new Order)->create([
                'user_id' => $user->id, 'vendor_id' => $vendor->id,
                'order_number' => 'ORD-GC-004', 'total_amount' => 5000,
                'net_amount' => 5000, 'status' => 'paid',
            ])->id,
            'vendor_id' => $vendor->id,
            'amount' => 5000,
            'balance' => 3500,
            'code' => 'GC-BAL-TEST',
            'status' => 'delivered',
            'delivered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/marketplace/gift-cards/balance?code=GC-BAL-TEST');

        $response->assertOk()
            ->assertJsonPath('data.balance', 3500);
    }

    public function test_can_validate_promo_code(): void
    {
        $user = $this->createUser();

        PromoCode::create([
            'code' => 'SAVE10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 1000,
            'max_uses' => 100,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/promo-codes/validate', [
            'code' => 'SAVE10',
            'order_amount' => 5000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', 'SAVE10');
    }

    public function test_can_apply_promo_code(): void
    {
        $user = $this->createUser();

        PromoCode::create([
            'code' => 'FLAT50',
            'discount_type' => 'fixed',
            'discount_value' => 500,
            'min_order_amount' => 1000,
            'max_uses' => 100,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/promo-codes/apply', [
            'code' => 'FLAT50',
            'order_amount' => 2000,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.discounted_amount', 1500);
    }

    public function test_can_earn_loyalty_points(): void
    {
        $user = $this->createUser();

        $this->app->make(LoyaltyService::class)->earnPoints(
            $user->id,
            5000,
            'purchase',
        );

        $response = $this->actingAs($user)->getJson('/api/v1/marketplace/loyalty/balance');

        $response->assertOk()
            ->assertJsonPath('data.points', 5);
    }

    public function test_can_redeem_loyalty_points(): void
    {
        $user = $this->createUser();

        $loyalty = $this->app->make(LoyaltyService::class);
        $loyalty->earnPoints($user->id, 100000, 'purchase');

        $response = $this->actingAs($user)->postJson('/api/v1/marketplace/loyalty/redeem', [
            'points' => 50,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.syp_value', 500);
    }
}
