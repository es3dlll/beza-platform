# 05 - كود الميغريشن الكامل (Migrations)

## جدول العملية

```php
<?php
// database/migrations/2024_01_01_000004_create_t9_pay-bills_table.php

use Illuminate\DatabaseMigrationsMigration;
use Illuminate\DatabaseSchemaBlueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
```
