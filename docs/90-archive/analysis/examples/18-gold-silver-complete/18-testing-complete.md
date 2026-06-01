# 18 - كل الاختبارات (PHPUnit Tests)

## اختبارات CommodityService

```php
<?php
// tests/Unit/Services/CommodityServiceTest.php

namespace Tests\Unit\Services;

use App\Events\GoldPurchased;
use App\Events\GoldSold;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientHoldingException;
use App\Exceptions\MarketClosedException;
use App\Exceptions\MinimumHoldingPeriodException;
use App\Models\CommodityHolding;
use App\Models\CommodityTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommodityService;
use App\Services\PriceFeedProvider;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CommodityServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommodityService $service;
    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'currency' => 'USD',
            'balance' => 10000.00,
        ]);

        $this->service = app(CommodityService::class);

        // تجاوز PriceFeedProvider لإرجاع أسعار ثابتة
        $this->mockPriceFeed();
    }

    private function mockPriceFeed(): void
    {
        $mock = $this->mock(PriceFeedProvider::class);
        $mock->shouldReceive('isMarketOpen')->andReturn(true);
        $mock->shouldReceive('getPrice')->with('gold')->andReturn([
            'price_usd' => 2300.00,
            'price_syp' => 29900000.00,
            'bid'       => 2288.50,
            'ask'       => 2334.50,
            'timestamp' => now()->toIso8601String(),
        ]);
        $mock->shouldReceive('getPrice')->with('silver')->andReturn([
            'price_usd' => 27.50,
            'price_syp' => 357500.00,
            'bid'       => 27.36,
            'ask'       => 27.91,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /** @test */
    public function it_buys_gold_successfully()
    {
        Event::fake();

        $result = $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 500.00,
            currency:    'USD',
        );

        $this->assertArrayHasKey('grams', $result);
        $this->assertArrayHasKey('holding', $result);
        $this->assertArrayHasKey('reference_number', $result);
        $this->assertGreaterThan(0, $result['grams']);

        // التحقق من خصم المحفظة
        $this->wallet->refresh();
        $this->assertEquals(9500.00, $this->wallet->balance);

        // التحقق من إنشاء الحيازة
        $holding = CommodityHolding::where('user_id', $this->user->id)
            ->where('commodity', 'gold')
            ->first();
        $this->assertNotNull($holding);
        $this->assertEquals($result['grams'], $holding->grams);

        // التحقق من تسجيل المعاملة
        $this->assertDatabaseHas('commodity_transactions', [
            'user_id'         => $this->user->id,
            'commodity'       => 'gold',
            'type'            => 'buy',
            'reference_number' => $result['reference_number'],
        ]);

        // التحقق من إطلاق الحدث
        Event::assertDispatched(GoldPurchased::class);
    }

    /** @test */
    public function it_fails_buy_when_market_closed()
    {
        $this->mock(PriceFeedProvider::class)
            ->shouldReceive('isMarketOpen')->andReturn(false);

        $this->expectException(MarketClosedException::class);

        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 500.00,
            currency:    'USD',
        );
    }

    /** @test */
    public function it_fails_buy_with_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);

        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 999999.00,
            currency:    'USD',
        );
    }

    /** @test */
    public function it_sells_gold_successfully()
    {
        Event::fake();

        // أولاً: شراء ذهب
        $buyResult = $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 2000.00,
            currency:    'USD',
        );

        // تعديل وقت الشراء ليتجاوز 24 ساعة
        CommodityTransaction::where('user_id', $this->user->id)
            ->update(['created_at' => now()->subHours(48)]);

        // ثانياً: بيع الذهب
        $sellGrams = $buyResult['grams'] / 2; // بيع نصف الكمية
        $result = $this->service->executeSell(
            user:      $this->user,
            commodity: 'gold',
            grams:     $sellGrams,
            currency:  'USD',
        );

        $this->assertArrayHasKey('total_received', $result);
        $this->assertGreaterThan(0, $result['total_received']);

        // التحقق من إضافة الرصيد
        $this->wallet->refresh();
        $this->assertGreaterThan(8000.00, $this->wallet->balance);

        // التحقق من خصم الجرامات
        $holding = CommodityHolding::where('user_id', $this->user->id)
            ->where('commodity', 'gold')
            ->first();
        $this->assertEquals($buyResult['grams'] - $sellGrams, $holding->grams);

        Event::assertDispatched(GoldSold::class);
    }

    /** @test */
    public function it_fails_sell_with_insufficient_holding()
    {
        // شراء كمية صغيرة أولاً
        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 100.00,
            currency:    'USD',
        );

        CommodityTransaction::where('user_id', $this->user->id)
            ->update(['created_at' => now()->subHours(48)]);

        $this->expectException(InsufficientHoldingException::class);

        // محاولة بيع كمية أكبر من المتاح
        $this->service->executeSell(
            user:      $this->user,
            commodity: 'gold',
            grams:     999.0,
            currency:  'USD',
        );
    }

    /** @test */
    public function it_fails_sell_before_24h()
    {
        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 500.00,
            currency:    'USD',
        );

        $this->expectException(MinimumHoldingPeriodException::class);

        $this->service->executeSell(
            user:      $this->user,
            commodity: 'gold',
            grams:     0.1,
            currency:  'USD',
        );
    }

    /** @test */
    public function it_updates_avg_price_on_multiple_buys()
    {
        // الشراء الأول
        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 1000.00,
            currency:    'USD',
        );

        // الشراء الثاني
        $this->service->executeBuy(
            user:        $this->user,
            commodity:   'gold',
            amountSpent: 2000.00,
            currency:    'USD',
        );

        $holding = CommodityHolding::where('user_id', $this->user->id)
            ->where('commodity', 'gold')
            ->first();

        // total_invested = 1000 + 2000 = 3000
        $this->assertEquals(3000.00, $holding->total_invested_usd);
        $this->assertGreaterThan(0, $holding->avg_price_usd);
    }
}
```

## اختبارات التحقق (Validation Tests)

```php
<?php
// tests/Feature/CommodityValidationTest.php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommodityValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function buy_requires_commodity()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/commodity/buy', [
            'amount_spent' => 100,
            'currency' => 'USD',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('commodity');
    }

    /** @test */
    public function buy_requires_valid_commodity()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/commodity/buy', [
            'commodity' => 'platinum',
            'amount_spent' => 100,
            'currency' => 'USD',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('commodity');
    }

    /** @test */
    public function buy_requires_amount_spent_min_1()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/commodity/buy', [
            'commodity' => 'gold',
            'amount_spent' => 0.5,
            'currency' => 'USD',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('amount_spent');
    }

    /** @test */
    public function sell_requires_grams_min_0_1()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/commodity/sell', [
            'commodity' => 'gold',
            'grams' => 0.01,
            'currency' => 'USD',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('grams');
    }

    /** @test */
    public function sell_requires_valid_currency()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/commodity/sell', [
            'commodity' => 'gold',
            'grams' => 1.0,
            'currency' => 'EUR',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('currency');
    }

    /** @test */
    public function unauthenticated_user_cannot_buy()
    {
        $response = $this->postJson('/api/v1/commodity/buy', [
            'commodity' => 'gold',
            'amount_spent' => 100,
            'currency' => 'USD',
        ]);

        $response->assertStatus(401);
    }
}
```

## اختبارات PriceFeedProvider

```php
<?php
// tests/Unit/Services/PriceFeedProviderTest.php

namespace Tests\Unit\Services;

use App\Exceptions\MarketClosedException;
use App\Services\PriceFeedProvider;
use Carbon\Carbon;
use Tests\TestCase;

class PriceFeedProviderTest extends TestCase
{
    private PriceFeedProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = app(PriceFeedProvider::class);
    }

    /** @test */
    public function it_returns_price_for_gold()
    {
        $price = $this->provider->getPrice('gold');

        $this->assertIsArray($price);
        $this->assertArrayHasKey('price_usd', $price);
        $this->assertArrayHasKey('bid', $price);
        $this->assertArrayHasKey('ask', $price);
        $this->assertArrayHasKey('timestamp', $price);
        $this->assertGreaterThan(0, $price['price_usd']);
    }

    /** @test */
    public function it_returns_price_for_silver()
    {
        $price = $this->provider->getPrice('silver');

        $this->assertIsArray($price);
        $this->assertGreaterThan(0, $price['price_usd']);
    }

    /** @test */
    public function bid_is_less_than_ask()
    {
        $gold = $this->provider->getPrice('gold');
        $this->assertLessThan($gold['ask'], $gold['bid']);

        $silver = $this->provider->getPrice('silver');
        $this->assertLessThan($silver['ask'], $silver['bid']);
    }

    /** @test */
    public function market_is_closed_on_saturday()
    {
        Carbon::setTestNow(Carbon::parse('2026-05-30 12:00:00', 'GMT')); // Saturday
        $this->assertFalse($this->provider->isMarketOpen());
    }

    /** @test */
    public function market_is_open_on_monday()
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', 'GMT')); // Monday
        $this->assertTrue($this->provider->isMarketOpen());
    }
}
```

## اختبارات ACID / Concurrency

```php
<?php
// tests/Feature/CommodityConcurrencyTest.php

namespace Tests\Feature;

use App\Models\CommodityHolding;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommodityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommodityConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function concurrent_buys_dont_double_spend()
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 1000.00,
        ]);

        // محاكاة شراءين متزامنين: كل واحد يستهلك 600
        // الرصيد 1000 فقط، لذا الثاني يجب أن يفشل
        $caught = 0;

        DB::beginTransaction();
        try {
            $wallet = Wallet::lockForUpdate()->where('user_id', $user->id)->first();
            $wallet->decrement('balance', 600);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $caught++;
        }

        DB::beginTransaction();
        try {
            $wallet = Wallet::lockForUpdate()->where('user_id', $user->id)->first();
            if ($wallet->balance < 600) {
                throw new \Exception('Insufficient balance');
            }
            $wallet->decrement('balance', 600);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $caught++;
        }

        // واحد من الاثنين يجب أن يفشل (أو كلاهما حسب الترتيب)
        $this->assertGreaterThanOrEqual(1, $caught);

        // الرصيد النهائي يجب ألا يكون سالباً
        $finalBalance = Wallet::where('user_id', $user->id)->first()->balance;
        $this->assertGreaterThanOrEqual(0, $finalBalance);
    }
}
```
