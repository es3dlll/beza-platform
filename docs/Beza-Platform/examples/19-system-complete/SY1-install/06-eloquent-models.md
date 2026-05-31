# 06 - نماذج Eloquent (Eloquent Models)

## لا يوجد موديل خاص بالمثبت

عملية التنصيب **لا تحتاج إلى Eloquent Model** خاص بها لأنها لا تتعامل مع جدول في قاعدة البيانات. كل عملياتها تعتمد على:

1. **فحص الخادم** — PHP functions مثل `extension_loaded()`, `exec()`
2. **PDO** — للاتصال بقاعدة البيانات مباشرة (ليس عبر Eloquent)
3. **ملف `.env`** — قراءة وكتابة باستخدام `file_get_contents`, `file_put_contents`
4. **Artisan** — استدعاء أوامر عبر `Illuminate\Support\Facades\Artisan`
5. **User Model** — فقط عند إنشاء المشرف الأول

## كيف يتم إنشاء المشرف الأول؟

```php
<?php
// يتم إنشاء المشرف داخل InstallerController@createAdmin
// يستخدم موديل User العادي

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'name'     => $request->input('name'),
    'email'    => $request->input('email'),
    'phone'    => $request->input('phone'),
    'password' => Hash::make($request->input('password')),
    'is_admin' => true,
    'email_verified_at' => now(),  // المشرف موثّق تلقائياً
]);
```

## إذا لم يكن هناك موديل، كيف نستخدم العلاقات؟

لا توجد علاقات. المثبت هو عملية **إجرائية (Procedural)** أكثر منها كائنية التوجه. لكنه يستفيد من:

| المكون | الدور |
|--------|-------|
| `InstallerController` | تنسيق الخطوات |
| `RequirementChecker` | فحص متطلبات الخادم |
| `EnvironmentConfigurator` | إدارة ملف `.env` |
| `User` (موديل) | إنشاء المشرف فقط |
| `Artisan` facade | تشغيل أوامر CLI |

## هل يمكن إنشاء Installer كـ Model؟

لا يوجد داعٍ. الأسباب:
- **لا يوجد جدول** — الموديل بلا جدول لا معنى له
- **لا علاقات** — لا يحتاج المثبت إلى `hasMany` أو `belongsTo`
- **تشغيل لمرة واحدة** — بعد الإكمال، لا يتم استخدامه مرة أخرى
- **لا بيانات ديناميكية** — كل الإعدادات مخزنة في `.env` وليس DB

## البديل: InstallServiceProvider

بدلاً من موديل، يتم تسجيل خدمة المثبت عبر `ServiceProvider`:

```php
<?php
// app/Providers/InstallServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class InstallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Install\RequirementChecker::class);
        $this->app->singleton(\App\Services\Install\EnvironmentConfigurator::class);
    }

    public function boot(): void
    {
        // التحقق من حالة المثبت عند كل طلب
        if (env('INSTALLER_LOCKED') !== true && !$this->app->runningInConsole()) {
            // توجيه المستخدم إلى /install
        }
    }
}
```
