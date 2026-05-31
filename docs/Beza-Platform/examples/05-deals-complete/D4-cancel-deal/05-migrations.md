# 05 - كود الميغريشن الكامل (Migrations)

_(نفس جداول D1 — لا توجد جداول جديدة)_

## إضافة أعمدة الإلغاء

```php
<?php
// database/migrations/2024_01_01_000014_add_cancel_fields_to_deals.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('risk_level');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
        });

        Schema::table('deal_investments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('profit_earned');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancelled_at']);
        });
        Schema::table('deal_investments', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
```
