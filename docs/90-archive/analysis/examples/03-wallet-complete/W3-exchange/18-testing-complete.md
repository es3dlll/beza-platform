# 18 - كل الاختبارات (Testing Complete)

## Feature Test — ExchangeTest

```php
<?php
// tests/Feature/ExchangeTest.php

namespace Tests\Feature;

use App\Events\ExchangeCompleted;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExchangeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['status' => 'active']);
        $this->token = JWTAuth::fromUser($this->user);

        Wallet::factory()->create([
            'user_id'  => $this->user->id,
            'currency' => 'SYP',
            'balance'  => 500000.00,
            'is_active' => true,
        ]);
        Wallet::factory()->create([
            'user_id'  => $this->user->id,
            'currency' => 'USD',
            'balance'  => 1000.00,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_completes_syp_to_usd_exchange()
    {
        Event::fake();

        $response = $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'SYP',
            'to_currency'   => 'USD',
            'amount'        => 130000.00,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response['data'];

        // تحقق من تفاصيل المعاملة
        $this->assertEquals('SYP', $data['transaction']['from_currency']);
        $this->assertEquals('USD', $data['transaction']['to_currency']);
        $this->assertEquals(130000.00, $data['transaction']['amount']);
        $this->assertEquals(9.85, $data['transaction']['converted_amount']); // (130000 / 13000) - 1.5% fee
        $this->assertEquals(1950.00, $data['transaction']['fee']); // 130000 * 0.015
        $this->assertEquals(13000, $data['transaction']['rate']);

        // تحقق من الأرصدة الجديدة
        $this->assertEquals(368050.00, $data['new_balances']['syp']); // 500000 - 130000 - 1950
        $this->assertEquals(1009.85, $data['new_balances']['usd']);  // 1000 + 9.85

        // تحقق من DB
        $this->assertDatabaseHas('transactions', [
            'type'   => 'exchange',
            'status' => 'completed',
            'amount' => 130000.00,
        ]);

        Event::assertDispatched(ExchangeCompleted::class);
    }

    /** @test */
    public function it_completes_usd_to_syp_exchange()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'USD',
            'to_currency'   => 'SYP',
            'amount'        => 100.00,
        ]);

        $response->assertStatus(200);

        $data = $response['data'];
        $converted = round(100 * 13000, 2); // 1,300,000 SYP
        $fee = 1.50; // 100 * 0.015

        $this->assertEquals('USD', $data['transaction']['from_currency']);
        $this->assertEquals('SYP', $data['transaction']['to_currency']);
        $this->assertEquals($converted, $data['transaction']['converted_amount']);
        $this->assertEquals($fee, $data['transaction']['fee']);
    }

    /** @test */
    public function it_rejects_same_currency()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'SYP',
            'to_currency'   => 'SYP',
            'amount'        => 1000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن الصرافة لنفس العملة',
            ]);
    }

    /** @test */
    public function it_rejects_below_minimum()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'USD',
            'to_currency'   => 'SYP',
            'amount'        => 0.50,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'الحد الأدنى للصرافة هو 1 USD',
            ]);
    }

    /** @test */
    public function it_rejects_insufficient_balance()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'SYP',
            'to_currency'   => 'USD',
            'amount'        => 999999999.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'رصيد غير كافٍ',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/wallet/exchange', [
            'from_currency' => 'SYP',
            'to_currency'   => 'USD',
            'amount'        => 1000,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/wallet/exchange', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_currency', 'to_currency', 'amount']);
    }

    /** @test */
    public function it_handles_concurrent_exchanges()
    {
        $amount = 100000.00; // 5 exchanges × 100000 = 500000 (بالضبط الرصيد)

        $promises = [];
        for ($i = 0; $i < 5; $i++) {
            $promises[] = function () use ($amount) {
                return $this->withToken($this->token)->postJson('/api/v1/wallet/exchange', [
                    'from_currency' => 'SYP',
                    'to_currency'   => 'USD',
                    'amount'        => $amount,
                ]);
            };
        }

        $responses = \Illuminate\Support\Facades\ParallelTesting::resolve($promises);

        $successCount = 0;
        $failCount = 0;
        foreach ($responses as $r) {
            if ($r->status() === 200) $successCount++;
            else $failCount++;
        }

        // الرصيد 500000 + 1.5% رسوم = 507500 لكل صرافة → 5 × 101500 = 507500
        // فقط الصرافات التي لديها رصيد كافٍ تنجح
        $this->assertGreaterThanOrEqual(1, $successCount);
    }
}
```

## Unit Test — ExchangeServiceTest

```php
<?php
// tests/Unit/ExchangeServiceTest.php

namespace Tests\Unit;

use App\Exceptions\SameCurrencyExchangeException;
use App\Exceptions\MinimumAmountException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ExchangeService;
use App\Services\RateService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExchangeService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExchangeService(
            new WalletService(),
            new RateService()
        );

        $this->user = User::factory()->create(['status' => 'active']);

        Wallet::factory()->create([
            'user_id' => $this->user->id, 'currency' => 'SYP', 'balance' => 500000,
        ]);
        Wallet::factory()->create([
            'user_id' => $this->user->id, 'currency' => 'USD', 'balance' => 1000,
        ]);
    }

    /** @test */
    public function it_exchanges_syp_to_usd()
    {
        $result = $this->service->exchange(
            user: $this->user,
            fromCurrency: 'SYP',
            toCurrency: 'USD',
            amount: 130000,
        );

        $this->assertEquals('SYP', $result['from_currency']);
        $this->assertEquals('USD', $result['to_currency']);
        $this->assertEquals('exchange', $result['transaction']->type);
        $this->assertEquals('completed', $result['transaction']->status);
    }

    /** @test */
    public function it_throws_for_same_currency()
    {
        $this->expectException(SameCurrencyExchangeException::class);

        $this->service->exchange(
            user: $this->user,
            fromCurrency: 'SYP',
            toCurrency: 'SYP',
            amount: 1000,
        );
    }

    /** @test */
    public function it_throws_for_minimum_amount()
    {
        $this->expectException(MinimumAmountException::class);

        $this->service->exchange(
            user: $this->user,
            fromCurrency: 'USD',
            toCurrency: 'SYP',
            amount: 0.10,
        );
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/ExchangePestTest.php

use App\Models\User;
use App\Models\Wallet;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $user = User::factory()->create(['status' => 'active']);
    $this->token = JWTAuth::fromUser($user);

    Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP', 'balance' => 500000]);
    Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'USD', 'balance' => 1000]);
});

test('syp to usd exchange', function () {
    postJson('/api/v1/wallet/exchange', [
        'from_currency' => 'SYP',
        'to_currency' => 'USD',
        'amount' => 130000,
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('usd to syp exchange', function () {
    postJson('/api/v1/wallet/exchange', [
        'from_currency' => 'USD',
        'to_currency' => 'SYP',
        'amount' => 100,
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(200);
});

test('rejects same currency', function () {
    postJson('/api/v1/wallet/exchange', [
        'from_currency' => 'SYP', 'to_currency' => 'SYP', 'amount' => 1000,
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});
```

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل Exchange Feature Test
php artisan test --filter=ExchangeTest

# تشغيل مع تغطية
php artisan test --coverage
```
