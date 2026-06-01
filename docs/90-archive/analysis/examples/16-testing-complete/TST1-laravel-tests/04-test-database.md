# 04 - قاعدة بيانات الاختبار (Test Database)

## إعداد البيئة

```env
# .env.testing
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beza_testing
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
FILESYSTEM_DISK=local
```

## RefreshDatabase Trait

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

// يعيد تشغيل الترحيلات قبل كل اختبار
// = كل اختبار يبدأ بقاعدة بيانات نظيفة

// بديل أسرع: DatabaseTransactions
use Illuminate\Foundation\Testing\DatabaseTransactions;

// يلف كل اختبار في DB::transaction
// أسرع لأنه لا يعيد إنشاء الجداول
```

## Seeders للاختبارات

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // مستخدم افتراضي للاختبارات
        $user = User::create([
            'uuid' => 'test-user-uuid',
            'name' => 'مستخدم اختبار',
            'phone' => '963900000001',
            'password' => Hash::make('password'),
            'pin_code' => Hash::make('1234'),
            'status' => 'active',
            'kyc_status' => 'verified',
        ]);

        // محافظ المستخدم
        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'wallet_number' => '630000000001',
            'balance' => 1000,
            'is_active' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'SYP',
            'wallet_number' => '620000000001',
            'balance' => 1000000,
            'is_active' => true,
        ]);
    }
}
```
