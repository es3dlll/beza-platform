# Shared Test Data Factories

> Single source of truth for factory definitions used across ALL feature tests. Every feature references these factories — never redefine defaults.

## UserFactory

### Default State
```php
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'full_name' => fake()->name('ar_SA'),           // Arabic name
            'phone' => '+963' . fake()->numerify('944#######'),
            'email' => fake()->unique()->safeEmail(),
            'pin_hash' => Hash::make('1234', ['rounds' => 4]), // Test cost only!
            'kyc_level' => 1,
            'role' => 'user',
            'preferred_locale' => 'ar',
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'device_trust_score' => 'trusted',
            'created_at' => now()->subDays(30),
            'tenant_id' => Tenant::factory(),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `unverified()` | `kyc_level: 0, phone_verified_at: null` | Fresh user, no KYC |
| `kycLevel1()` | `kyc_level: 1` | Basic KYC (default) |
| `kycLevel2()` | `kyc_level: 2` | Verified KYC |
| `kycLevel3()` | `kyc_level: 3` | Full KYC |
| `inactive()` | `is_active: false` | Suspended user |
| `withWallet($balance = 0)` | Has `Wallet` relationship | Creates wallet |
| `withDevice($platform = 'android')` | Has `Device` relationship | Creates device |
| `support()` | `role: 'support'` | Support agent |
| `agent()` | `role: 'agent'` | Field agent |
| `merchant()` | `role: 'merchant'` | Merchant |
| `arabicLocale()` | `preferred_locale: 'ar'` | Arabic (default) |
| `englishLocale()` | `preferred_locale: 'en'` | English |

### Usage Examples
```php
User::factory()->unverified()->create();
User::factory()->kycLevel2()->withWallet(500000)->create();
User::factory()->agent()->withWallet(2000000)->create();
User::factory()->count(10)->kycLevel1()->create();
```

## WalletFactory

### Default State
```php
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'balance' => 0,
            'currency' => 'SYP',
            'daily_sent' => 0,
            'daily_received' => 0,
            'monthly_sent' => 0,
            'monthly_received' => 0,
            'status' => 'active',
            'last_activity_at' => now(),
            'created_at' => now()->subDays(30),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `active()` | `status: 'active'` | Active (default) |
| `frozen()` | `status: 'frozen'` | Frozen by compliance |
| `closed()` | `status: 'closed'` | Closed wallet |
| `withBalance($amount)` | `balance: $amount` | Set specific balance |
| `nearDailyLimit()` | `daily_sent: 450000` | 90% of daily limit |

### Usage Examples
```php
Wallet::factory()->withBalance(100000)->create();
Wallet::factory()->nearDailyLimit()->for($user)->create();
Wallet::factory()->count(3)->create(['user_id' => $user->id]);
```

## TransactionFactory

### Default State
```php
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'reference_id' => 'REF-' . strtoupper(Str::random(8)),
            'sender_wallet_id' => Wallet::factory(),
            'recipient_wallet_id' => Wallet::factory(),
            'amount' => fake()->numberBetween(1000, 100000),
            'fee' => fake()->numberBetween(0, 5000),
            'total' => fn(array $attrs) => $attrs['amount'] + $attrs['fee'],
            'currency' => 'SYP',
            'type' => 'transfer',
            'status' => 'completed',
            'description' => fake()->sentence(3),
            'sender_balance_before' => 500000,
            'sender_balance_after' => fn(array $attrs) => 500000 - $attrs['total'],
            'recipient_balance_before' => 100000,
            'recipient_balance_after' => fn(array $attrs) => 100000 + $attrs['amount'],
            'idempotency_key' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'completed_at' => now(),
            'created_at' => now()->subHour(),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `pending()` | `status: 'pending'` | Awaiting processing |
| `completed()` | `status: 'completed', completed_at: now()` | Completed (default) |
| `failed()` | `status: 'failed'` | Failed |
| `reversed()` | `status: 'reversed'` | Reversed/rolled back |
| `offline()` | `status: 'pending', is_offline: true` | Created offline |
| `large()` | `amount: 900000` | Near AML threshold |
| `crossBorder()` | `currency: 'USD'` | Cross-border transaction |
| `cashIn()` | `type: 'cash_in'` | Agent cash-in |
| `cashOut()` | `type: 'cash_out'` | Agent cash-out |
| `billPay()` | `type: 'bill_pay'` | Bill payment |
| `yesterday()` | `created_at: now()->subDay()` | Created yesterday |

### Usage Examples
```php
Transaction::factory()->pending()->create();
Transaction::factory()->large()->crossBorder()->create();
Transaction::factory()->count(5)->yesterday()->forSender($wallet)->create();
```

## AgentFactory

### Default State
```php
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory()->agent(),
            'agent_code' => 'AG-' . strtoupper(Str::random(6)),
            'float_balance' => 500000,
            'float_limit' => 5000000,
            'daily_cash_in' => 0,
            'daily_cash_out' => 0,
            'daily_limit' => 2000000,
            'status' => 'active',
            'location_lat' => 33.5131,
            'location_lng' => 36.2765,
            'address' => 'دمشق، سورية',
            'commission_rate' => 0.005,                  // 0.5%
            'commission_balance' => 0,
            'last_reconciliation_at' => now()->subDay(),
            'created_at' => now()->subDays(90),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `active()` | `status: 'active'` | Active agent (default) |
| `suspended()` | `status: 'suspended'` | Suspended by compliance |
| `pending()` | `status: 'pending'` | Awaiting approval |
| `lowFloat()` | `float_balance: 10000` | Low float balance |
| `maxedOut()` | `daily_cash_in: 2000000` | Hit daily limit |
| `withTransactions($count)` | Has transaction history | Agent with activity |

### Usage Examples
```php
Agent::factory()->lowFloat()->create();
Agent::factory()->maxedOut()->for($user)->create();
Agent::factory()->count(5)->active()->create();
```

## MerchantFactory

### Default State
```php
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory()->merchant(),
            'merchant_code' => 'MERC-' . strtoupper(Str::random(8)),
            'business_name' => fake()->company(),
            'business_type' => 'retail',
            'settlement_wallet_id' => Wallet::factory(),
            'settlement_period' => 'daily',               // daily | weekly | monthly
            'commission_rate' => 0.02,                    // 2%
            'daily_volume' => 0,
            'monthly_volume' => 0,
            'status' => 'active',
            'mcc_code' => '5411',                         // Grocery
            'created_at' => now()->subDays(60),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `active()` | `status: 'active'` | Active (default) |
| `pending()` | `status: 'pending'` | Awaiting approval |
| `suspended()` | `status: 'suspended'` | Suspended |
| `highVolume()` | `monthly_volume: 50000000` | High-volume merchant |
| `weekly()` | `settlement_period: 'weekly'` | Weekly settlement |
| `withWallet()` | Sets up settlement wallet | Merchant with wallet |

### Usage Examples
```php
Merchant::factory()->highVolume()->create();
Merchant::factory()->weekly()->create();
Merchant::factory()->count(3)->active()->create();
```

## DeviceFactory

### Default State
```php
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'user_id' => User::factory(),
            'fcm_token' => 'fcm-' . Str::random(64),
            'platform' => 'android',
            'platform_version' => '14',
            'app_version' => '1.2.3',
            'device_model' => 'Samsung Galaxy S24',
            'device_fingerprint' => Str::random(32),
            'is_trusted' => true,
            'last_seen_at' => now(),
            'created_at' => now()->subDays(30),
        ];
    }
}
```

### States
| State | Overrides | Description |
|-------|-----------|-------------|
| `android()` | `platform: 'android'` | Android device (default) |
| `ios()` | `platform: 'ios'` | iOS device |
| `new()` | `is_trusted: false, last_seen_at: null` | New/untrusted |
| `compromised()` | `is_trusted: false` | Flagged as compromised |

---

## Factory Organization Rules

1. **Shared factories** live in `database/factories/` at the platform level
2. **Feature-specific factories** live in the feature's `tests/Factories/` directory
3. **Never redefine** a shared factory's definition in a feature test — always extend
4. **States** are additive: stack multiple states for complex scenarios
5. **Dates** use relative dates (e.g., `now()->subDays(30)`) so tests don't expire
6. **Pin in tests**: Always use cost=4 (not 12) in tests to avoid slow hashing
