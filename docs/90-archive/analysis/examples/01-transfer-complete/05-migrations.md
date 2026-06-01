# 05 - كود الميغريشن الكامل (Migrations)

## جدول المحافظ (wallets)

```php
<?php
// database/migrations/2024_01_01_000002_create_wallets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('currency', ['SYP', 'USD']);
            $table->string('wallet_number', 20)->unique();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('frozen_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index('wallet_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
```

## جدول المعاملات (transactions)

```php
<?php
// database/migrations/2024_01_01_000003_create_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_usd', 15, 2);
            $table->enum('type', [
                'deposit', 'withdraw', 'transfer', 'exchange',
                'merchant_payment', 'agent_cash_in', 'agent_cash_out',
                'investment', 'investment_profit', 'card_load',
                'card_payment', 'fee'
            ]);
            $table->enum('status', [
                'pending', 'processing', 'completed',
                'failed', 'cancelled', 'refunded'
            ])->default('pending');
            $table->string('reference_number', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['from_wallet_id', 'status']);
            $table->index(['to_wallet_id', 'status']);
            $table->index(['type', 'created_at']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
```

## ملاحظات الميغريشن

| الجدول | التفاصيل |
|--------|----------|
| wallets | ينشأ مع المستخدم (حدث `created`) — محفظة SYP + USD لكل مستخدم |
| transactions | يسجل كل حركة مالية — الـ reference_number يُنشأ عشوائياً |
| المهلة | كل جدول يعمل ضمن `InnoDB` لضمان ACID |

## إنشاء المحفظة التلقائي (Event)

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    Registered::class => [
        CreateUserWallets::class,  // مستمع جديد
    ],
];
```

```php
<?php
// app/Listeners/CreateUserWallets.php

use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;

class CreateUserWallets
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        foreach (['SYP', 'USD'] as $currency) {
            Wallet::create([
                'user_id'       => $user->id,
                'currency'      => $currency,
                'wallet_number' => $this->generateWalletNumber($currency),
                'balance'       => 0.00,
                'frozen_balance'=> 0.00,
                'is_active'     => true,
            ]);
        }
    }

    private function generateWalletNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '62' : '63';
        $digits = $prefix . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        // ضمان التفرد
        while (Wallet::where('wallet_number', $digits)->exists()) {
            $digits = $prefix . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        }
        return $digits;
    }
}
```
