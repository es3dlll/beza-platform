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

## جدول المعاملات (transactions) — مع دعم exchange

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

## جدول أسعار الصرف (اختياري — للتحديث الديناميكي)

```php
<?php
// database/migrations/2024_01_01_000004_create_exchange_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);    // SYP, USD
            $table->string('to_currency', 3);      // SYP, USD
            $table->decimal('rate', 15, 6);         // سعر الصرف
            $table->decimal('buy_rate', 15, 6);     // سعر الشراء
            $table->decimal('sell_rate', 15, 6);    // سعر البيع
            $table->decimal('fee_percentage', 5, 2)->default(1.50); // رسوم %
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();

            $table->index(['from_currency', 'to_currency', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
```

## Config (مصدر سعر الصرف الأساسي)

```php
<?php
// config/beza.php

return [
    'exchange' => [
        'rates' => [
            'SYP_TO_USD' => env('SYP_TO_USD_RATE', 13000),  // 1 USD = 13000 SYP
            'USD_TO_SYP' => env('USD_TO_SYP_RATE', 13000),
        ],
        'fee_percentage' => env('EXCHANGE_FEE_PERCENTAGE', 1.50), // 1.5%
        'min_amounts' => [
            'SYP' => env('MIN_EXCHANGE_SYP', 1000),
            'USD' => env('MIN_EXCHANGE_USD', 1),
        ],
    ],
];
```
