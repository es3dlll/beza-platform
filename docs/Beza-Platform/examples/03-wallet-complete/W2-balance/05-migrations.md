# 05 - كود الميغريشن الكامل (Migrations)

## جدول المحافظ (wallets) — نفس الجدول لجميع العمليات

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

## ملاحظات

| الجدول | التفاصيل |
|--------|----------|
| wallets | يحتوي على balance + frozen_balance لكل محفظة |
| balance | الرصيد الحالي (قابل للاستخدام) |
| frozen_balance | رصيد مجمد (معلق في معاملة) |
| is_active | هل المحفظة نشطة؟ |

## إضافة Cache Clear Trigger (مستقبلاً)

يمكن إضافة MySQL Trigger لمسح Cache تلقائياً عند تحديث الرصيد:

```sql
-- MySQL Trigger (اختياري — الأفضل استخدام Laravel Events)
DELIMITER //
CREATE TRIGGER clear_balance_cache AFTER UPDATE ON wallets
FOR EACH ROW
BEGIN
    -- سيتم التنظيف عبر Laravel Event
END //
DELIMITER ;
```

لكن نفضل استخدام Laravel Events + Listeners لمسح Cache:
```php
// في WalletService أو EventServiceProvider
Cache::forget("balance:user:{$userId}");
```
