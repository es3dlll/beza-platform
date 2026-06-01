# 05 - هجرات قاعدة البيانات

## 1. جدول الاتصالات (contacts)

```php
<?php
// database/migrations/2026_01_01_000001_create_contacts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('phone', 20)->nullable();
            $table->string('subject', 200);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
```

## 2. جدول المشتركين (subscribers)

```php
<?php
// database/migrations/2026_01_01_000002_create_subscribers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100)->unique();
            $table->string('name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source', 50)->nullable()->comment('footer, hero, modal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
```

## 3. جدول استفسارات التجار (merchant_inquiries)

```php
<?php
// database/migrations/2026_01_01_000003_create_merchant_inquiries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('contact_name', 100);
            $table->string('email', 100);
            $table->string('phone', 20);
            $table->string('business_type', 50)->nullable();
            $table->decimal('monthly_volume', 15, 2)->nullable()->comment('حجم المعاملات الشهري');
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'closed'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_inquiries');
    }
};
```

## 4. جدول استفسارات الوكلاء (agent_inquiries)

```php
<?php
// database/migrations/2026_01_01_000004_create_agent_inquiries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('contact_name', 100);
            $table->string('email', 100);
            $table->string('phone', 20);
            $table->string('city', 50);
            $table->boolean('has_office')->default(false);
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'converted', 'closed'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_inquiries');
    }
};
```

## تشغيل الهجرات

```bash
php artisan migrate
php artisan migrate:fresh --seed  # إعادة إنشاء الجداول مع بيانات تجريبية
```
