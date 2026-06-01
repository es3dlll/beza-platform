# 05 - كود الميغريشن الكامل (Migrations)

## جدول الصفقات (deals)

```php
<?php
// database/migrations/2024_01_01_000010_create_deals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0.00);
            $table->enum('currency', ['SYP', 'USD']);
            $table->decimal('expected_profit_percentage', 5, 2);
            $table->decimal('profit_actual', 5, 2)->nullable();
            $table->unsignedInteger('duration_days');
            $table->string('category', 100);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->enum('status', [
                'pending', 'review', 'active',
                'filled', 'completed', 'cancelled'
            ])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
```

## جدول استثمارات الصفقات (deal_investments)

```php
<?php
// database/migrations/2024_01_01_000011_create_deal_investments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('investor_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_usd', 15, 2);
            $table->enum('currency', ['SYP', 'USD']);
            $table->decimal('profit_earned', 15, 2)->nullable();
            $table->enum('status', ['active', 'completed', 'refunded'])->default('active');
            $table->timestamps();

            $table->unique(['deal_id', 'investor_id']);
            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_investments');
    }
};
```
