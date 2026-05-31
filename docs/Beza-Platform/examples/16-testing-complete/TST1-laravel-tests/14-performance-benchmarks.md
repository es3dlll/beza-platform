# 14 - اختبارات الأداء (Performance Benchmarks)

## معايير الأداء

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function transfer_under_200ms()
    {
        $sender = User::factory()->create(['pin_code' => Hash::make('1234')]);
        Wallet::factory()->create(['user_id' => $sender->id, 'currency' => 'USD', 'balance' => 100000]);

        $receiver = User::factory()->create();
        Wallet::factory()->create(['user_id' => $receiver->id, 'currency' => 'USD', 'balance' => 0]);

        $token = JWTAuth::fromUser($sender);

        $start = microtime(true);

        $this->withToken($token)
            ->postJson('/api/v1/transfer', [
                'to_phone' => $receiver->phone,
                'amount' => 100,
                'currency' => 'USD',
                'pin' => '1234',
            ]);

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(200, $duration, "Transfer took {$duration}ms (exceeds 200ms)");
    }

    /** @test */
    public function auth_under_100ms()
    {
        User::factory()->create([
            'phone' => '963900000001',
            'password' => Hash::make('password'),
        ]);

        $start = microtime(true);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '963900000001',
            'password' => 'password',
        ]);

        $duration = (microtime(true) - $start) * 1000;
        $this->assertLessThan(100, $duration, "Login took {$duration}ms");
    }
}
```

## MySQL Query Count

```php
/** @test */
public function transfer_uses_optimal_queries()
{
    \DB::enableQueryLog();

    // ... إجراء تحويل ...

    $queries = \DB::getQueryLog();

    // يجب ألا يتجاوز 10 استعلامات للتحويل
    $this->assertLessThanOrEqual(10, count($queries));

    // لا يجب أن يكون هناك N+1 queries
    $fromWallets = array_filter($queries, fn($q) => str_contains($q['query'], 'wallets'));
    $this->assertLessThanOrEqual(3, count($fromWallets));
}
```
