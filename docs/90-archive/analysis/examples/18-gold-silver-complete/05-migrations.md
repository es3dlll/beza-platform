# 05 - كود الميغريشن الكامل (Migrations)

## جدول: commodity_prices

```php
<?php
// database/migrations/2024_01_01_000010_create_commodity_prices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_prices', function (Blueprint $table) {
            $table->id();
            $table->enum('commodity', ['gold', 'silver']);
            $table->decimal('price_usd', 15, 2);
            $table->decimal('price_syp', 15, 2);
            $table->decimal('bid_usd', 15, 2);
            $table->decimal('ask_usd', 15, 2);
            $table->string('source', 100);
            $table->timestamp('timestamp');

            $table->index(['commodity', 'timestamp'], 'idx_commodity_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_prices');
    }
};
```

## جدول: commodity_holdings

```php
<?php
// database/migrations/2024_01_01_000011_create_commodity_holdings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('commodity', ['gold', 'silver']);
            $table->decimal('grams', 15, 4)->default(0.0000);
            $table->decimal('avg_price_usd', 15, 2)->default(0.00);
            $table->decimal('total_invested_usd', 15, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['user_id', 'commodity'], 'uq_user_commodity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_holdings');
    }
};
```

## جدول: commodity_transactions

```php
<?php
// database/migrations/2024_01_01_000012_create_commodity_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('commodity', ['gold', 'silver']);
            $table->enum('type', ['buy', 'sell']);
            $table->decimal('grams', 15, 4);
            $table->decimal('price_usd', 15, 2);
            $table->decimal('total_usd', 15, 2);
            $table->decimal('fee', 15, 2)->default(0.00);
            $table->string('reference_number', 50)->unique();
            $table->enum('status', [
                'pending', 'completed', 'failed', 'cancelled',
            ])->default('completed');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'type'], 'idx_user_type');
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index('reference_number', 'idx_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_transactions');
    }
};
```

## جدول: commodity_orders

```php
<?php
// database/migrations/2024_01_01_000013_create_commodity_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commodity_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('type', ['buy', 'sell']);
            $table->enum('commodity', ['gold', 'silver']);
            $table->decimal('grams', 15, 4);
            $table->enum('price_type', ['market', 'limit']);
            $table->decimal('limit_price', 15, 2)->nullable();
            $table->enum('status', [
                'pending', 'executed', 'cancelled', 'expired',
            ])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_user_order_status');
            $table->index(['status', 'expires_at'], 'idx_order_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commodity_orders');
    }
};
```

## ملخص الميغريشن

| الجدول | المحرك | الميزات الخاصة |
|--------|--------|----------------|
| commodity_prices | InnoDB | Index مركب (commodity, timestamp) لتسريع استعلام آخر سعر |
| commodity_holdings | InnoDB | UNIQUE(user_id, commodity) — حيازة واحدة لكل مستخدم/سلعة |
| commodity_transactions | InnoDB | reference_number فريد — status يدعم pending للـ 30s lock |
| commodity_orders | InnoDB | price_type: market/limit — يدعم أوامر limit المعلقة |
