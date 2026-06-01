# 05 - اختبار الميغريشنات (Test Migrations)

اختبار الميغريشنات يضمن أن بنية قاعدة البيانات صحيحة قبل تشغيل التطبيق في الإنتاج.

## استخدام RefreshDatabase

`RefreshDatabase` trait يعيد تشغيل كل الميغريشنات بين الاختبارات:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_expected_columns()
    {
        $columns = Schema::getColumnListing('users');

        $this->assertContains('id', $columns);
        $this->assertContains('name', $columns);
        $this->assertContains('email', $columns);
        $this->assertContains('password', $columns);
        $this->assertContains('phone', $columns);
        $this->assertContains('role', $columns);
        $this->assertContains('status', $columns);
        $this->assertContains('email_verified_at', $columns);
        $this->assertContains('remember_token', $columns);
        $this->assertContains('created_at', $columns);
        $this->assertContains('updated_at', $columns);
    }
}
```

## اختبار أن الميغريشن ينشئ الجداول بالأعمدة الصحيحة

```php
public function test_transactions_table_columns_and_types()
{
    // التحقق من وجود الجدول
    $this->assertTrue(Schema::hasTable('transactions'));

    // التحقق من أنواع الأعمدة
    $this->assertEquals('bigint', DB::connection()
        ->getDoctrineColumn('transactions', 'id')->getType()->getName());
    $this->assertEquals('decimal', DB::connection()
        ->getDoctrineColumn('transactions', 'amount')->getType()->getName());
    $this->assertEquals('string', DB::connection()
        ->getDoctrineColumn('transactions', 'status')->getType()->getName());
    $this->assertEquals('datetime', DB::connection()
        ->getDoctrineColumn('transactions', 'created_at')->getType()->getName());
}
```

## اختبار أن الميغريشن يتراجع بشكل صحيح (Rollback)

```php
public function test_migration_can_be_rolled_back()
{
    $migration = '2026_05_27_000001_create_wallets_table';

    // تنفيذ الترحيل
    Artisan::call('migrate', ['--path' => "database/migrations/{$migration}.php"]);
    $this->assertTrue(Schema::hasTable('wallets'));

    // التراجع عن الترحيل
    Artisan::call('migrate:rollback', ['--path' => "database/migrations/{$migration}.php"]);
    $this->assertFalse(Schema::hasTable('wallets'));
}
```

## اختبار الفهارس الفريدة (Unique Indexes)

```php
public function test_unique_indexes_exist()
{
    // التحقق من وجود index فريد على email في جدول users
    $indexes = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_unique'");
    $this->assertNotEmpty($indexes, 'Index users_email_unique is missing');

    // محاولة إدخال بريد مكرر يجب أن يفشل
    User::create(['email' => 'test@example.com', 'name' => 'User 1', 'password' => bcrypt('pass')]);
    $this->expectException(\Illuminate\Database\QueryException::class);
    User::create(['email' => 'test@example.com', 'name' => 'User 2', 'password' => bcrypt('pass')]);
}
```

## اختبار قيود المفاتيح الخارجية (Foreign Key Constraints)

```php
public function test_foreign_key_constraints()
{
    // إنشاء مستخدم
    $user = User::factory()->create();

    // إنشاء محفظة مرتبطة بالمستخدم
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);
    $this->assertNotNull($wallet);

    // حذف المستخدم — يجب حذف المحفظة تلقائياً (cascade)
    $user->delete();
    $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);

    // محاولة إنشاء محفظة بمستخدم غير موجود — يجب الفشل
    $this->expectException(\Illuminate\Database\QueryException::class);
    Wallet::factory()->create(['user_id' => 9999]);
}

// مثال على تعريف migration مع العلاقات
// database/migrations/2026_05_27_000002_create_wallets_table.php
// $table->foreignId('user_id')->constrained()->cascadeOnDelete();
// $table->foreignId('currency_id')->constrained('currencies');
```

## اختبار Seeder (بذور البيانات)

```php
public function test_seeder_creates_expected_data()
{
    // تشغيل DatabaseSeeder
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // التحقق من وجود مستخدم مدير افتراضي
    $admin = User::where('email', 'admin@bezaplatform.com')->first();
    $this->assertNotNull($admin);
    $this->assertEquals('admin', $admin->role);

    // التحقق من إنشاء العملات الأساسية
    $this->assertDatabaseHas('currencies', ['code' => 'SYP']);
    $this->assertDatabaseHas('currencies', ['code' => 'USD']);
    $this->assertDatabaseHas('currencies', ['code' => 'EUR']);

    // التحقق من وجود إعدادات افتراضية
    $this->assertDatabaseHas('settings', ['key' => 'default_currency']);
}

public function test_seeder_is_idempotent()
{
    // تشغيل البذور مرتين — يجب ألا يسبب أخطاء
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // التأكد من عدم وجود بيانات مكررة
    $this->assertEquals(1, User::where('email', 'admin@bezaplatform.com')->count());
}
```

## ملخص الاختبارات المطلوبة لكل table

| الجدول | الأعمدة | Indexes | Foreign Keys |
|--------|---------|---------|--------------|
| users | 10+ أعمدة | unique(email) | — |
| wallets | 8 أعمدة | unique(user_id, currency) | user_id → users |
| transactions | 12 عموداً | idx_type_status_created | user_id, wallet_id |
| currencies | 4 أعمدة | unique(code) | — |
| audit_logs | 8 أعمدة | idx_event_type, idx_user_id | user_id → users |
