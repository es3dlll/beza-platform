# 05 - الميغريشن (Migrations)

## Create card_transactions Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('merchant', 255);
            $table->string('category', 100)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for report queries
            $table->index('card_id');
            $table->index('created_at');
            $table->index('category');
            $table->index('status');
            $table->index(['card_id', 'created_at']);
            $table->index(['card_id', 'category']);
            $table->index(['card_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_transactions');
    }
};
```

## Migration Notes

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT UNSIGNED | Primary key |
| card_id | BIGINT UNSIGNED | FK to cards table |
| amount | DECIMAL(15,2) | Transaction amount |
| currency | VARCHAR(3) | ISO currency code |
| merchant | VARCHAR(255) | Merchant name |
| category | VARCHAR(100) | Spending category |
| status | ENUM | Transaction state |
| created_at | TIMESTAMP | Transaction date |

## Indexes for Report Performance

```sql
-- Composite indexes are critical for report queries
CREATE INDEX idx_card_created ON card_transactions(card_id, created_at);
CREATE INDEX idx_card_category ON card_transactions(card_id, category);
CREATE INDEX idx_card_status ON card_transactions(card_id, status);
```

## Rollback

```php
public function down(): void
{
    Schema::dropIfExists('card_transactions');
}
```
