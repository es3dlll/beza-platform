# 05 - كود الميغريشن الكامل (Migrations)

_(نفس جداول D1 — لا توجد جداول جديدة، فقط استعلامات تحديث)_

## إضافة عمود profit_actual إلى deals (إذا لم يكن موجوداً)

```php
<?php
// database/migrations/2024_01_01_000013_add_profit_actual_to_deals.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->decimal('profit_actual', 5, 2)->nullable()->after('expected_profit_percentage');
            $table->timestamp('completed_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['profit_actual', 'completed_at']);
        });
    }
};
```
