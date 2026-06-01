# 18 - كل الاختبارات (Testing Complete)

## Feature Test — TransferTest

```php
<?php
// tests/Feature/TransferTest.php

namespace Tests\Feature;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransferTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $sender;
    private User $receiver;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء المستخدمين مع المحافظ
        $this->sender = User::factory()->create([
            'phone'    => '963944123456',
            'pin_code' => Hash::make('1234'),
            'status'   => 'active',
        ]);

        $this->receiver = User::factory()->create([
            'phone'    => '963944654321',
            'status'   => 'active',
        ]);

        // إنشاء المحافظ
        Wallet::factory()->create([
            'user_id'  => $this->sender->id,
            'currency' => 'USD',
            'balance'  => 500.00,
            'is_active' => true,
        ]);
        Wallet::factory()->create([
            'user_id'  => $this->sender->id,
            'currency' => 'SYP',
            'balance'  => 100000.00,
            'is_active' => true,
        ]);
        Wallet::factory()->create([
            'user_id'  => $this->receiver->id,
            'currency' => 'USD',
            'balance'  => 0.00,
            'is_active' => true,
        ]);
        Wallet::factory()->create([
            'user_id'  => $this->receiver->id,
            'currency' => 'SYP',
            'balance'  => 0.00,
            'is_active' => true,
        ]);

        // مصادقة
        $this->token = JWTAuth::fromUser($this->sender);
    }

    /** @test */
    public function it_completes_a_successful_transfer()
    {
        Event::fake();

        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم التحويل بنجاح',
            ]);

        // تحقق من وجود reference_number
        $this->assertStringStartsWith('BZ', $response['data']['transaction']['reference_number']);

        // تحقق من الرصيد الجديد
        $this->assertEquals(400.00, $response['data']['new_balance']);

        // تحقق من DB
        $this->assertDatabaseHas('transactions', [
            'type'   => 'transfer',
            'status' => 'completed',
            'amount' => 100.00,
        ]);

        // تحقق من أرصدة المحافظ
        $this->assertEquals(400.00, $this->sender->usdWallet->fresh()->balance);
        $this->assertEquals(100.00, $this->receiver->usdWallet->fresh()->balance);

        // تحقق من Event
        Event::assertDispatched(TransactionCompleted::class);
    }

    /** @test */
    public function it_fails_when_recipient_not_found()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944999999',
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'المستلم غير موجود',
            ]);
    }

    /** @test */
    public function it_fails_for_self_transfer()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944123456', // نفس رقم المرسل
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'لا يمكن التحويل إلى نفسك',
            ]);
    }

    /** @test */
    public function it_fails_with_invalid_pin()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '9999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'رمز PIN غير صحيح',
            ]);
    }

    /** @test */
    public function it_fails_with_insufficient_balance()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 999999.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'رصيد غير كافٍ',
            ]);
    }

    /** @test */
    public function it_fails_when_daily_limit_exceeded()
    {
        // إنشاء معاملات سابقة تستهلك جزءاً من الحد اليومي
        $senderWallet = $this->sender->usdWallet;
        Transaction::factory()->count(5)->create([
            'from_wallet_id' => $senderWallet->id,
            'to_wallet_id'   => $this->receiver->usdWallet->id,
            'amount'         => 400.00,
            'type'           => 'transfer',
            'status'         => 'completed',
            'created_at'     => now(),
        ]);

        // 5 × 400 = 2000 (الحد كامل)
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 1.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'تجاوز الحد اليومي للتحويل',
            ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_phone', 'amount', 'currency', 'pin']);
    }

    /** @test */
    public function it_rejects_invalid_currency()
    {
        $response = $this->withToken($this->token)->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 100,
            'currency' => 'EUR',
            'pin'      => '1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/transfer', [
            'to_phone' => '963944654321',
            'amount'   => 100.00,
            'currency' => 'USD',
            'pin'      => '1234',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_concurrent_transfers()
    {
        // محاولة إرسال 10 تحويلات متزامنة
        $amount = 50.00; // 500 / 50 = 10 تحويلات بالضبط
        $walletId = $this->sender->usdWallet->id;

        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = function () use ($amount) {
                return $this->withToken($this->token)->postJson('/api/v1/transfer', [
                    'to_phone' => '963944654321',
                    'amount'   => $amount,
                    'currency' => 'USD',
                    'pin'      => '1234',
                ]);
            };
        }

        // تنفيذها بشكل متزامن
        $responses = \Illuminate\Support\Facades\ParallelTesting::resolve($promises);

        $successCount = 0;
        $failCount = 0;
        foreach ($responses as $r) {
            if ($r->status() === 201) $successCount++;
            else $failCount++;
        }

        // بالضبط 10 تنجح لأن 10 × 50 = 500 (الرصيد كامل)
        $this->assertEquals(10, $successCount);
        $this->assertEquals(0, $failCount);
    }
}
```

## Unit Test — TransferService

```php
<?php
// tests/Unit/TransferServiceTest.php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPinException;
use App\Exceptions\SelfTransferException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransferService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $service;
    private User $sender;
    private User $receiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransferService(new WalletService());

        $this->sender = User::factory()->create([
            'pin_code' => Hash::make('1234'),
            'status'   => 'active',
        ]);
        $this->receiver = User::factory()->create([
            'status' => 'active',
        ]);

        Wallet::factory()->create([
            'user_id'  => $this->sender->id,
            'currency' => 'USD',
            'balance'  => 500.00,
        ]);
        Wallet::factory()->create([
            'user_id'  => $this->receiver->id,
            'currency' => 'USD',
            'balance'  => 0.00,
        ]);
    }

    /** @test */
    public function it_transfers_money_successfully()
    {
        $result = $this->service->transfer(
            fromUser: $this->sender,
            toPhone:  $this->receiver->phone,
            amount:   100,
            currency: 'USD',
            pin:      '1234',
        );

        $this->assertEquals(400.00, $result['new_balance']);
        $this->assertEquals('transfer', $result['transaction']->type);
        $this->assertEquals('completed', $result['transaction']->status);
    }

    /** @test */
    public function it_throws_for_self_transfer()
    {
        $this->expectException(SelfTransferException::class);

        $this->service->transfer(
            fromUser: $this->sender,
            toPhone:  $this->sender->phone,
            amount:   100,
            currency: 'USD',
            pin:      '1234',
        );
    }

    /** @test */
    public function it_throws_for_invalid_pin()
    {
        $this->expectException(InvalidPinException::class);

        $this->service->transfer(
            fromUser: $this->sender,
            toPhone:  $this->receiver->phone,
            amount:   100,
            currency: 'USD',
            pin:      'wrong',
        );
    }

    /** @test */
    public function it_throws_for_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);

        $this->service->transfer(
            fromUser: $this->sender,
            toPhone:  $this->receiver->phone,
            amount:   999999,
            currency: 'USD',
            pin:      '1234',
        );
    }
}
```

## Pest Tests (بديل PHPUnit)

```php
<?php
// tests/Feature/TransferPestTest.php

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->sender = User::factory()->create([
        'phone'    => '963944123456',
        'pin_code' => Hash::make('1234'),
        'status'   => 'active',
    ]);
    $this->receiver = User::factory()->create([
        'phone'  => '963944654321',
        'status' => 'active',
    ]);

    Wallet::factory()->create([
        'user_id' => $this->sender->id, 'currency' => 'USD', 'balance' => 500,
    ]);
    Wallet::factory()->create([
        'user_id' => $this->receiver->id, 'currency' => 'USD', 'balance' => 0,
    ]);

    $this->token = JWTAuth::fromUser($this->sender);
});

test('successful transfer', function () {
    postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount'   => 100,
        'currency' => 'USD',
        'pin'      => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(201)
        ->assertJson(['success' => true]);
});

test('rejects self transfer', function () {
    postJson('/api/v1/transfer', [
        'to_phone' => '963944123456',
        'amount'   => 100,
        'currency' => 'USD',
        'pin'      => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('rejects invalid pin', function () {
    postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount'   => 100,
        'currency' => 'USD',
        'pin'      => '0000',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('rejects insufficient balance', function () {
    postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount'   => 999999,
        'currency' => 'USD',
        'pin'      => '1234',
    ], ['Authorization' => 'Bearer ' . $this->token])
        ->assertStatus(422);
});

test('requires authentication', function () {
    postJson('/api/v1/transfer', [
        'to_phone' => '963944654321',
        'amount'   => 100,
        'currency' => 'USD',
        'pin'      => '1234',
    ])->assertStatus(401);
});
```

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
php artisan test

# تشغيل Feature Test فقط
php artisan test --filter=TransferTest

# تشغيل Unit Test فقط
php artisan test --filter=TransferServiceTest

# تشغيل مع تغطية
php artisan test --coverage

# تشغيل مع Pest
./vendor/bin/pest --filter=TransferPestTest
```
