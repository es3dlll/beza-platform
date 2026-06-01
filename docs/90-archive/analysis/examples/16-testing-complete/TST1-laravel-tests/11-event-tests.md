# 11 - اختبارات الأحداث (Event Tests)

```php
<?php

namespace Tests\Feature;

use App\Events\TransactionCompleted;
use App\Listeners\SendTransactionNotification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_transaction_completed_event()
    {
        Event::fake();

        $sender = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $sender->id, 'currency' => 'USD', 'balance' => 1000]);
        $receiver = User::factory()->create();
        Wallet::factory()->create(['user_id' => $receiver->id, 'currency' => 'USD', 'balance' => 0]);

        $token = JWTAuth::fromUser($sender);

        $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $receiver->phone,
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        Event::assertDispatched(TransactionCompleted::class);
    }

    /** @test */
    public function it_does_not_dispatch_event_on_failed_transfer()
    {
        Event::fake();

        $sender = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $sender->id, 'currency' => 'USD', 'balance' => 10]);

        $token = JWTAuth::fromUser($sender);

        $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => '963900000002',
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        Event::assertNotDispatched(TransactionCompleted::class);
    }

    /** @test */
    public function listener_attached_to_event()
    {
        Event::fake();

        // تحقق من وجود المستمع
        $listeners = Event::getListeners(TransactionCompleted::class);
        $this->assertNotEmpty($listeners);
    }
}
```
