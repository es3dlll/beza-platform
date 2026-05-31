# 05 - الهجرات (Migrations) لجداول التسوية

## نظرة عامة
ملفات الهجرة تنشئ جداول قاعدة البيانات اللازمة لنظام تسوية أرصدة التجار. الجداول مصممة لدعم العملات المتعددة، تتبع العمولات والرسوم، وربط التحويلات البنكية.

## جدول merchant_settlements

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')
                  ->constrained('merchants')
                  ->onDelete('cascade');

            // فترة التسوية (من - إلى)
            $table->date('period_start');
            $table->date('period_end');

            // المبالغ المالية
            $table->decimal('gross_amount', 15, 2);           // إجمالي المبيعات
            $table->decimal('commission_percentage', 5, 2);   // نسبة عمولة Beza
            $table->decimal('commission_amount', 15, 2);      // قيمة العمولة
            $table->decimal('transfer_fee', 15, 2);           // رسوم التحويل البنكي
            $table->decimal('refunds_deducted', 15, 2)
                  ->default(0);                               // خصم المرتجعات
            $table->decimal('chargebacks_deducted', 15, 2)
                  ->default(0);                               // خصم التصريفات
            $table->decimal('net_amount', 15, 2);             // الصافي بعد جميع الخصومات

            // العملة والحالة
            $table->enum('currency', ['SYP', 'USD', 'EUR', 'AED']);

            // معلومات التحويل البنكي
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('iban', 34)->nullable();           // International Bank Account Number
            $table->string('swift_code', 11)->nullable();     // SWIFT/BIC code
            $table->string('bank_transaction_ref', 100)
                  ->nullable()
                  ->comment('المرجع من بنك API');

            $table->string('failure_reason', 500)
                  ->nullable()
                  ->comment('سبب فشل التحويل إن وجد');

            // الحالة والتوقيت
            $table->enum('status', [
                'pending',           // قيد الانتظار
                'processing',        // قيد المعالجة
                'completed',         // تم بنجاح
                'failed',            // فشل
                'partially_completed', // نجح جزئياً
                'cancelled',         // ملغي
            ])->default('pending');

            $table->timestamp('settlement_date')->nullable();
            $table->timestamp('bank_transfer_initiated_at')->nullable();
            $table->timestamp('bank_transfer_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // للأرشفة بدلاً من الحذف
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_settlements');
    }
};
```

## جدول settlement_items (بنود التسوية)

```php
Schema::create('settlement_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('settlement_id')
          ->constrained('merchant_settlements')
          ->onDelete('cascade');

    $table->morphs('sourceable'); // polymorphic: transaction, refund, chargeback
    $table->decimal('amount', 15, 2);
    $table->string('type', 50);   // 'payment', 'refund', 'chargeback', 'commission'
    $table->string('description', 255)->nullable();

    $table->timestamps();

    // فهرس للبحث السريع
    $table->index(['settlement_id', 'type']);
});
```

## جدول merchant_bank_accounts (حسابات التجار البنكية)

```php
Schema::create('merchant_bank_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('merchant_id')
          ->constrained('merchants')
          ->onDelete('cascade');

    $table->string('bank_name', 100);
    $table->string('account_holder_name', 100);
    $table->string('account_number', 50);
    $table->string('iban', 34);
    $table->string('swift_code', 11)->nullable();
    $table->string('currency', 3)->default('USD');
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);

    $table->timestamps();
    $table->softDeletes();

    // منع ازدواجية الحسابات
    $table->unique(['merchant_id', 'iban']);
});
```

## الفهارس (Indexes)

```php
// إضافة الفهارس بعد إنشاء الجداول
Schema::table('merchant_settlements', function (Blueprint $table) {
    $table->index('status');
    $table->index('settlement_date');
    $table->index(['merchant_id', 'status']);
    $table->index(['merchant_id', 'settlement_date']);
    $table->index(['merchant_id', 'currency']);
});
```

## ملخص الجداول

| الجدول | الوصف | المفاتيح الخارجية |
|--------|-------|------------------|
| `merchant_settlements` | طلبات التسوية الرئيسية | merchant_id → merchants |
| `settlement_items` | تفاصيل بنود التسوية | settlement_id → merchant_settlements |
| `merchant_bank_accounts` | حسابات التجار البنكية | merchant_id → merchants |
