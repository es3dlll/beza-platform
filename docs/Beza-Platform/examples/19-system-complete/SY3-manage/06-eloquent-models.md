# 06 - نماذج Eloquent: لا توجد نماذج (Eloquent Models: No Models)

<div dir="rtl">

## نظرة عامة

عملية SY3-manage لا تحتوي على أي نماذج Eloquent مخصصة. هذا لأن العملية لا تتعامل مع قاعدة البيانات بشكل مباشر، بل تتعامل مع:

1. **أوامر Artisan**: تنفيذ أوامر Laravel عبر `Artisan::call()`
2. **نظام الملفات**: قراءة وكتابة الملفات (السجلات، النسخ الاحتياطية)
3. **أوامر shell**: تنفيذ أوامر مثل `mysqldump` عبر `Process` أو `exec`
4. **معلومات النظام**: جمع معلومات عن PHP، Laravel، والبيئة

## لماذا لا توجد نماذج؟

| المبرر | الشرح المفصل |
|--------|-------------|
| لا توجد جداول | بدون جداول قاعدة بيانات، لا حاجة لنماذج Eloquent |
| العمليات إجرائية | كل عملية SY3-manage هي إجراء (procedure) وليس كيان (entity) |
| التعامل مع الملفات | نسخ الملفات الاحتياطية، السجلات - كلها ملفات وليس سجلات في DB |
| استدعاء أوامر | Artisan و exec لا يتطلبان Eloquent |

## ما هي الكلاسات المستخدمة بدلاً من ذلك؟

بدلاً من نماذج Eloquent، تستخدم SY3-manage كلاسات خدمة (Service Classes):

### 1. CacheManager
```php
// لا يوجد نموذج Eloquent
// يستخدم Artisan::call() لأوامر الكاش
namespace App\Services\System;

class CacheManager
{
    public function clear(): array { /* ... */ }
    public function optimize(): array { /* ... */ }
}
```

### 2. LogManager
```php
// لا يوجد نموذج Eloquent
// يتعامل مع ملفات السجل مباشرة
namespace App\Services\System;

class LogManager
{
    public function view(): string { /* ... */ }
    public function clear(): bool { /* ... */ }
    public function list(): array { /* ... */ }
    public function show(string $file): string { /* ... */ }
}
```

### 3. BackupManager
```php
// لا يوجد نموذج Eloquent
// يستخدم mysqldump وينشئ ملفات .sql.gz
namespace App\Services\System;

class BackupManager
{
    public function create(): array { /* ... */ }
    public function list(): array { /* ... */ }
    public function restore(string $id): bool { /* ... */ }
    public function delete(string $id): bool { /* ... */ }
}
```

### 4. QueueManager
```php
// لا يوجد نموذج Eloquent
// يتفاعل مع Queue Worker عبر Artisan
namespace App\Services\System;

class QueueManager
{
    public function status(): array { /* ... */ }
    public function restart(): bool { /* ... */ }
}
```

### 5. MaintenanceManager
```php
// لا يوجد نموذج Eloquent
// يستخدم Artisan down/up
namespace App\Services\System;

class MaintenanceManager
{
    public function toggle(bool $enabled, ?string $message, ?int $retry): array { /* ... */ }
}
```

### 6. SystemInfoCollector
```php
// لا يوجد نموذج Eloquent
// يجمع معلومات النظام
namespace App\Services\System;

class SystemInfoCollector
{
    public function collect(): array { /* ... */ }
}
```

## التعامل مع البيانات (بدون Eloquent)

### قراءة ملفات النسخ الاحتياطية
```php
// استخدام Illuminate\Support\Facades\Storage بدلاً من Eloquent
$backups = Storage::disk('local')->files('backups');
// يعيد مصفوفة من أسماء الملفات
```

### قراءة ملفات السجل
```php
// استخدام File facade أو SplFileObject
use Illuminate\Support\Facades\File;

$content = File::get(storage_path('logs/laravel.log'));
$lines = explode("\n", $content);
$lastLines = array_slice($lines, -100);
```

### معلومات النظام
```php
// استخدام دوال PHP المضمنة
$phpVersion = phpversion();
$laravelVersion = app()->version();
$environment = app()->environment();
```

## نموذج Eloquent الوحيد المستخدم (من تطبيقات أخرى)

النموذج الوحيد الذي قد تتفاعل معه SY3-manage هو نموذج `User` للتحقق من صلاحية المشرف:

```php
// app/Models/User.php
// مستخدم فقط للتحقق: $user->role === 'admin'
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // ...
}
```

## الخلاصة

SY3-manage لا تحتاج نماذج Eloquent لأنها تعمل فوق طبقة النظام (system layer) وليس طبقة البيانات (data layer). كل التفاعلات هي مع Artisan، الملفات، وأوامر shell. هذا يبسط الكود ويجعله أكثر قابلية للاختبار والصيانة.

</div>
