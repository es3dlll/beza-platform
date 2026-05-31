# 18 - كل الاختبارات (Testing Complete)

## Feature Test — BalanceTest

```php
<?php
// tests/Feature/BalanceTest.php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BalanceTest extends TestCase
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
            'user_id'        => $this->user->id,
            'currency'       => 'SYP',
            'balance'        => 150000.00,
            'frozen_balance' => 5000.00,
        ]);

        Wallet::factory()->create([
            'user_id'        => $this->user->id,
            'currency'       => 'USD',
            'balance'        => 500.00,
            'frozen_balance' => 0.00,
        ]);
    }

    /** @test */
    public function it_returns_both_wallet_balances()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response['data'];

        $this->assertArrayHasKey('syp', $data);
        $this->assertArrayHasKey('usd', $data);

        // تحقق من SYP
        $this->assertEquals(150000.00, $data['syp']['balance']);
        $this->assertEquals(5000.00, $data['syp']['frozen']);
        $this->assertEquals(145000.00, $data['syp']['available']);

        // تحقق من USD
        $this->assertEquals(500.00, $data['usd']['balance']);
        $this->assertEquals(0.00, $data['usd']['frozen']);
        $this->assertEquals(500.00, $data['usd']['available']);
    }

    /** @test */
    public function it_uses_cache_on_second_request()
    {
        // الطلب الأول — Cache MISS
        $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        // الطلب الثاني — Cache HIT
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200);
        $this->assertEquals(150000.00, $response['data']['syp']['balance']);
    }

    /** @test */
    public function it_clears_cache_after_wallet_update()
    {
        // جلب الرصيد أولاً — يخزن في Cache
        $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        // تحديث الرصيد — يمسح Cache
        $wallet = $this->user->sypWallet;
        $wallet->increment('balance', 1000);

        // يجب أن يكون Cache قد مُسح (via WalletService)
        Cache::shouldReceive('forget')
            ->with("balance:user:{$this->user->id}")
            ->once();
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/wallet/balance');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_wallet_number_for_each_currency()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        $this->assertStringStartsWith('62', $response['data']['syp']['wallet_number']);
        $this->assertStringStartsWith('63', $response['data']['usd']['wallet_number']);
    }

    /** @test */
    public function it_handles_zero_balance()
    {
        // مستخدم بدون رصيد
        $poorUser = User::factory()->create(['status' => 'active']);
        $token = JWTAuth::fromUser($poorUser);

        Wallet::factory()->create([
            'user_id'  => $poorUser->id,
            'currency' => 'SYP',
            'balance'  => 0.00,
        ]);
        Wallet::factory()->create([
            'user_id'  => $poorUser->id,
            'currency' => 'USD',
            'balance'  => 0.00,
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/wallet/balance');

        $response->assertStatus(200);
        $this->assertEquals(0.00, $response['data']['syp']['balance']);
        $this->assertEquals(0.00, $response['data']['usd']['balance']);
    }

    /** @test */
    public function it_enforces_rate_limit()
    {
        // محاولة 61 طلب (الحد 60)
        for ($i = 0; $i < 60; $i++) {
            $this->withToken($this->token)
                ->getJson('/api/v1/wallet/balance');
        }

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/wallet/balance');

        $response->assertStatus(429);
    }
}
```

## Unit Test — BalanceServiceTest

```php
<?php
// tests/Unit/BalanceServiceTest.php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use App\Services\BalanceService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private BalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BalanceService(new WalletService());
    }

    /** @test */
    public function it_returns_cached_balance()
    {
        $user = User::factory()->create();

        Cache::shouldReceive('get')
            ->with("balance:user:{$user->id}")
            ->once()
            ->andReturn(['cached' => 'data']);

        $result = $this->service->getBalance($user);
        $this->assertEquals(['cached' => 'data'], $result);
    }

    /** @test */
    public function it_returns_balance_from_db_when_cache_miss()
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id, 'currency' => 'SYP', 'balance' => 100,
        ]);
        Wallet::factory()->create([
            'user_id' => $user->id, 'currency' => 'USD', 'balance' => 50,
        ]);

        Cache::shouldReceive('get')
            ->with("balance:user:{$user->id}")
            ->once()
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once();

        $result = $this->service->getBalance($user);
        $this->assertCount(2, $result);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/BalancePestTest.php

use App\Models\User;
use App\Models\Wallet;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $user = User::factory()->create(['status' => 'active']);
    $this->token = JWTAuth::fromUser($user);

    Wallet::factory()->create([
        'user_id' => $user->id, 'currency' => 'SYP', 'balance' => 1000,
    ]);
    Wallet::factory()->create([
        'user_id' => $user->id, 'currency' => 'USD', 'balance' => 50,
    ]);
});

test('returns balance successfully', function () {
    getJson('/api/v1/wallet/balance', [
        'Authorization' => 'Bearer ' . $this->token,
    ])->assertStatus(200)
      ->assertJson(['success' => true]);
});

test('requires authentication', function () {
    getJson('/api/v1/wallet/balance')->assertStatus(401);
});

test('has both currencies', function () {
    $response = getJson('/api/v1/wallet/balance', [
        'Authorization' => 'Bearer ' . $this->token,
    ]);

    expect($response['data'])->toHaveKeys(['syp', 'usd']);
    expect($response['data']['syp']['balance'])->toBe(1000.00);
    expect($response['data']['usd']['balance'])->toBe(50.00);
});
```

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل Balance Feature Test
php artisan test --filter=BalanceTest

# تشغيل Unit Test
php artisan test --filter=BalanceServiceTest

# تشغيل مع تغطية
php artisan test --coverage
```
