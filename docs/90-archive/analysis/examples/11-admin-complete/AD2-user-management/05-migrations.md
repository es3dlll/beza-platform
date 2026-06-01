# 05 - كود الميغريشن (Migrations)

جدول users موجود مسبقاً. نضيف تحسينات لإدارة المستخدمين:

## إضافة Indexes لدعم البحث

```php
<?php
// database/migrations/2024_06_01_000010_add_user_management_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['status', 'deleted_at'], 'idx_admin_user_list');
            $table->index(['kyc_status', 'deleted_at'], 'idx_admin_kyc_filter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_admin_user_list');
            $table->dropIndex('idx_admin_kyc_filter');
        });
    }
};
```

## جدول سجل نشاط المشرف

```php
<?php
// database/migrations/2024_06_01_000011_create_admin_activity_log_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users');
            $table->string('action'); // list, view, suspend, activate, block, delete
            $table->string('target_type')->nullable(); // user, merchant, agent
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_log');
    }
};
```
