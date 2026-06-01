# 05 - كود الميغريشن الكامل (Migrations)

_(نفس جداول D1 — deals + deal_investments — مع إضافة indexes للاستعلامات)_

## إضافة Index محسن للاستعلام عن الصفقات النشطة

```php
<?php
// database/migrations/2024_01_01_000012_add_deal_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->index(['status', 'current_amount', 'target_amount'], 'idx_deal_available');
        });

        Schema::table('deal_investments', function (Blueprint $table) {
            $table->index(['investor_id', 'status'], 'idx_investor_status');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('idx_deal_available');
        });
        Schema::table('deal_investments', function (Blueprint $table) {
            $table->dropIndex('idx_investor_status');
        });
    }
};
```

## ملاحظات

| الجدول | التفاصيل |
|--------|----------|
| deals | current_amount يزداد مع كل استثمار |
| deal_investments | يسجل كل استثمار في الصفقة |
| UNIQUE(deal_id,investor_id) | مستثمر واحد لكل صفقة مرة واحدة |
| status=active | الاستثمار نشط (غير مسترجع) |
