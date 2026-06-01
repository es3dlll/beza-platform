# 05 - الترحيلات: لا توجد ترحيلات لقاعدة البيانات (Migrations: No Database Migrations)

<div dir="rtl">

## نظرة عامة

عملية SY3-manage لا تتطلب أي ترحيلات (migrations) لقاعدة البيانات. هذا قرار معماري متعمد للأسباب التالية:

## لماذا لا توجد ترحيلات؟

### 1. لا توجد جداول مخصصة
SY3-manage لا تنشئ أي جداول جديدة في قاعدة البيانات. جميع العمليات تعتمد على:
- أوامر **Artisan** المضمنة في Laravel (cache:clear, queue:restart, down, up)
- أوامر **exec/shell** (mysqldump, mysql)
- **نظام الملفات** (قراءة وكتابة ملفات السجل والنسخ الاحتياطية)

### 2. لا حاجة لتخزين دائم
العمليات التي تديرها SY3-manage هي عمليات مؤقتة:
- مسح الكاش: عملية مؤقتة لا تحتاج للتسجيل
- إدارة السجلات: تتعامل مع ملفات موجودة مسبقاً
- حالة قائمة الانتظار: قراءة فقط
- النسخ الاحتياطي: يخزن كملفات خارج قاعدة البيانات

### 3. مبدأ فصل المسؤوليات
من الأفضل عدم خلط بيانات التطبيق مع بيانات إدارة النظام. استخدام الملفات للنسخ الاحتياطية يسمح باستعادة البيانات حتى لو كانت قاعدة البيانات تالفة.

## ملفات الترحيل ذات الصلة (من تطبيقات أخرى)

على الرغم من أن SY3-manage لا تحتاج ترحيلات خاصة، إلا أن بعض الجداول التي تتفاعل معها موجودة من خلال ترحيلات أخرى في التطبيق:

| اسم الترحيل | الجدول | العلاقة مع SY3-manage |
|-------------|--------|-----------------------|
| `2014_10_12_000000_create_users_table.php` | `users` | التحقق من دور المشرف (role: admin) |
| `2019_08_19_000000_create_failed_jobs_table.php` | `failed_jobs` | QueueManager يعرض الوظائف الفاشلة |
| `2019_12_14_000001_create_personal_access_tokens_table.php` | `personal_access_tokens` | مصادقة JWT (إذا تم تخزينها) |
| `2020_01_01_000000_create_jobs_table.php` | `jobs` | QueueManager يقرأ حالة قائمة الانتظار |

## تنفيذ أي ترحيلات مستقبلية (للتوثيق فقط)

إذا أردت في المستقبل إضافة جدول لتسجيل عمليات النظام، يمكن استخدام الترحيل التالي:

```php
<?php
// database/migrations/2026_05_27_000001_create_system_operations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * تشغيل الترحيل لإنشاء جدول عمليات النظام
     */
    public function up(): void
    {
        Schema::create('system_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('operation', 100); // cache:clear, backup:create, etc
            $table->enum('status', ['success', 'failed']);
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * عكس الترحيل وحذف الجدول
     */
    public function down(): void
    {
        Schema::dropIfExists('system_operations');
    }
};
```

## التحقق من وجود الترحيلات

```bash
# التحقق من وجود جميع الترحيلات المطلوبة
php artisan migrate:status

# تشغيل أي ترحيلات معلقة
php artisan migrate
```

## الخلاصة

SY3-manage تتبع مبدأ **"لا حاجة لقاعدة بيانات لإدارة النظام"**. جميع العمليات تتم عبر الأدوات المضمنة في Laravel وأدوات النظام. هذا يبسط عملية النشر (deployment) ويجعل إعادة التعيين (rollback) أسهل بكثير - فقط استعد النسخة الاحتياطية من الملف.

</div>
