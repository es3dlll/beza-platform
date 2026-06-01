# 18 - كل الاختبارات (Testing Complete)

## Feature Test — CreateWalletTest

```php
<?php
// tests/Feature/CreateWalletTest.php

namespace Tests\Feature;

use App\Events\WalletCreated;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CreateWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateWalletTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_creates_two_wallets_on_user_registration()
    {
        Event::fake([WalletCreated::class]);

        $response = $this->postJson('/api/v1/register', [
            'name'     => 'أحمد',
            'phone'    => '963944123456',
            'password' => 'password123',
            'pin_code' => '1234',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم التسجيل بنجاح',
            ]);

        // تحقق من وجود المحافظ
        $userId = $response['data']['user']['id'];

        $this->assertDatabaseHas('wallets', [
            'user_id'  => $userId,
            'currency' => 'SYP',
            'balance'  => 0.00,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id'  => $userId,
            'currency' => 'USD',
            'balance'  => 5.00, // هدية
        ]);

        // تحقق من أرقام المحافظ
        $sypWallet = Wallet::where('user_id', $userId)->where('currency', 'SYP')->first();
        $usdWallet = Wallet::where('user_id', $userId)->where('currency', 'USD')->first();

        $this->assertStringStartsWith('62', $sypWallet->wallet_number);
        $this->assertStringStartsWith('63', $usdWallet->wallet_number);

        // تحقق من معاملة الهدية
        $this->assertDatabaseHas('transactions', [
            'to_wallet_id' => $usdWallet->id,
            'amount'       => 5.00,
            'type'         => 'deposit',
            'status'       => 'completed',
            'description'  => 'هدية ترحيبية',
        ]);

        Event::assertDispatched(WalletCreated::class);
    }

    /** @test */
    public function it_generates_unique_wallet_numbers()
    {
        $numbers = [];
        $service = app(CreateWalletService::class);

        for ($i = 0; $i < 100; $i++) {
            $number = $service->generateWalletNumber('SYP');
            $this->assertNotContains($number, $numbers);
            $numbers[] = $number;
        }

        $this->assertCount(100, $numbers);
    }

    /** @test */
    public function it_prevents_duplicate_wallet_creation()
    {
        $user = User::factory()->create(['status' => 'active']);

        // إنشاء المحفظة أول مرة
        Wallet::factory()->create([
            'user_id'  => $user->id,
            'currency' => 'SYP',
        ]);

        // المحاولة الثانية يجب أن تفشل
        $this->expectException(\App\Exceptions\WalletsAlreadyExistException::class);

        $service = app(CreateWalletService::class);
        $service->createWallets($user);
    }

    /** @test */
    public function it_validates_wallet_number_prefix()
    {
        $service = app(CreateWalletService::class);

        $sypNumber = $service->generateWalletNumber('SYP');
        $usdNumber = $service->generateWalletNumber('USD');

        $this->assertEquals('62', substr($sypNumber, 0, 2));
        $this->assertEquals('63', substr($usdNumber, 0, 2));
        $this->assertEquals(12, strlen($sypNumber));
        $this->assertEquals(12, strlen($usdNumber));
    }

    /** @test */
    public function it_requires_active_user()
    {
        $user = User::factory()->create(['status' => 'pending']);

        $this->expectException(\App\Exceptions\UserNotActiveException::class);

        $service = app(CreateWalletService::class);
        $service->createWallets($user);
    }

    /** @test */
    public function it_creates_wallets_for_existing_users_via_seeder()
    {
        $user = User::factory()->create(['status' => 'active']);

        $service = app(CreateWalletService::class);
        $result = $service->createWallets($user);

        $this->assertArrayHasKey('wallets', $result);
        $this->assertArrayHasKey('syp', $result['wallets']);
        $this->assertArrayHasKey('usd', $result['wallets']);
        $this->assertNotNull($result['bonus_transaction']);
    }
}
```

## Unit Test — CreateWalletServiceTest

```php
<?php
// tests/Unit/CreateWalletServiceTest.php

namespace Tests\Unit;

use App\Exceptions\UserNotActiveException;
use App\Exceptions\WalletsAlreadyExistException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CreateWalletService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateWalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreateWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreateWalletService(new WalletService());
    }

    /** @test */
    public function it_creates_both_wallets()
    {
        $user = User::factory()->create(['status' => 'active']);

        $result = $this->service->createWallets($user);

        $this->assertCount(2, $user->wallets);
        $this->assertEquals('SYP', $result['wallets']['syp']->currency);
        $this->assertEquals('USD', $result['wallets']['usd']->currency);
    }

    /** @test */
    public function it_adds_bonus_to_usd_wallet()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->service->createWallets($user);

        $usdWallet = $user->usdWallet;
        $this->assertEquals(5.00, $usdWallet->balance);
        $this->assertEquals(0.00, $user->sypWallet->balance);
    }

    /** @test */
    public function it_throws_for_inactive_user()
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->expectException(UserNotActiveException::class);
        $this->service->createWallets($user);
    }

    /** @test */
    public function it_throws_if_wallets_exist()
    {
        $user = User::factory()->create(['status' => 'active']);
        Wallet::factory()->create(['user_id' => $user->id, 'currency' => 'SYP']);

        $this->expectException(WalletsAlreadyExistException::class);
        $this->service->createWallets($user);
    }
}
```

## Pest Tests

```php
<?php
// tests/Feature/CreateWalletPestTest.php

use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

test('new user gets two wallets', function () {
    $response = postJson('/api/v1/register', [
        'name'     => 'أحمد',
        'phone'    => '963944123456',
        'password' => 'password123',
        'pin_code' => '1234',
    ]);

    $response->assertStatus(201);
    $userId = $response['data']['user']['id'];

    assertDatabaseHas('wallets', [
        'user_id' => $userId, 'currency' => 'SYP', 'balance' => 0.00,
    ]);
    assertDatabaseHas('wallets', [
        'user_id' => $userId, 'currency' => 'USD', 'balance' => 5.00,
    ]);
});

test('wallet numbers have correct prefixes', function () {
    $response = postJson('/api/v1/register', [
        'name'     => 'أحمد',
        'phone'    => '963944123456',
        'password' => 'password123',
        'pin_code' => '1234',
    ]);

    $wallets = $response['data']['wallets'];
    expect($wallets['syp']['wallet_number'])->toStartWith('62');
    expect($wallets['usd']['wallet_number'])->toStartWith('63');
});

test('registration requires pin_code', function () {
    postJson('/api/v1/register', [
        'name'     => 'أحمد',
        'phone'    => '963944123456',
        'password' => 'password123',
    ])->assertStatus(422);
});
```

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل CreateWallet Feature Test
php artisan test --filter=CreateWalletTest

# تشغيل Unit Test
php artisan test --filter=CreateWalletServiceTest

# تشغيل مع تغطية
php artisan test --coverage
```
