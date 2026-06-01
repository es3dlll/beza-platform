# 05 - الميغريشن (Migrations)

## جدول token_blacklist (JWT Logout)

JWT هو نظام Stateless — التوكن لا يُخزّن في قاعدة البيانات. لكن لتفعيل تسجيل الخروج، نستخدم جدول `token_blacklist` لإبطال التوكنات.

```php
<?php
// database/migrations/2024_01_01_000004_create_token_blacklist_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 255)->unique(); // معرف JWT الفريد
            $table->timestamp('expires_at');       // ينتهي بانتهاء التوكن الأصلي
            $table->timestamps();

            $table->index('expires_at'); // للحذف التلقائي للتوكنات المنتهية
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_blacklist');
    }
};
```

## آلية تسجيل الخروج

1. المستخدم يرسل طلب logout مع `Bearer token`
2. النظام يستخرج `jti` من التوكن
3. يُضاف `jti` إلى `token_blacklist` مع `expires_at` = تاريخ انتهاء التوكن
4. أي طلب لاحق بهذا التوكن يُرفض (قبل انتهاء صلاحيته)
5. التوكنات منتهية الصلاحية تُحذف تلقائياً من الجدول (أو عبر `token_blacklist:clear` command)

```bash
# حذف التوكنات منتهية الصلاحية من blacklist
php artisan jwt:clear
```
