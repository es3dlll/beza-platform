# 07 - قواعد التحقق (Validation Rules)

## Form Request — DatabaseRequest (إعدادات قاعدة البيانات)

```php
<?php
// app/Http/Requests/Install/DatabaseRequest.php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class DatabaseRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return env('INSTALLER_LOCKED') !== true;
    }

    public function rules(): array
    {
        return [
            'db_host' => [
                'required',
                'string',
                'max:255',
            ],
            'db_port' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'db_database' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9_]+$/',  // أسماء قواعد بيانات صالحة فقط
            ],
            'db_username' => [
                'required',
                'string',
                'max:255',
            ],
            'db_password' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'db_host.required'    => 'مضيف قاعدة البيانات مطلوب',
            'db_port.required'    => 'منفذ قاعدة البيانات مطلوب',
            'db_port.integer'     => 'المنفذ يجب أن يكون رقمًا',
            'db_port.min'         => 'المنفذ يجب أن يكون بين 1 و 65535',
            'db_port.max'         => 'المنفذ يجب أن يكون بين 1 و 65535',
            'db_database.required' => 'اسم قاعدة البيانات مطلوب',
            'db_database.regex'   => 'اسم قاعدة البيانات يجب أن يحتوي فقط على أحرف إنكليزية وأرقام وشرطة سفلية',
            'db_username.required' => 'اسم مستخدم قاعدة البيانات مطلوب',
        ];
    }
}
```

## Form Request — EnvironmentRequest (إعدادات البيئة)

```php
<?php
// app/Http/Requests/Install/EnvironmentRequest.php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class EnvironmentRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return env('INSTALLER_LOCKED') !== true;
    }

    public function rules(): array
    {
        return [
            'app_name' => [
                'required',
                'string',
                'max:255',
            ],
            'app_url' => [
                'required',
                'url',
                'max:255',
            ],
            'app_env' => [
                'required',
                'in:local,staging,production',
            ],
            'redis_host' => [
                'required',
                'string',
                'max:255',
            ],
            'redis_port' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
            'redis_password' => [
                'nullable',
                'string',
                'max:255',
            ],
            'mail_host' => [
                'nullable',
                'string',
                'max:255',
            ],
            'mail_port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'mail_username' => [
                'nullable',
                'string',
                'max:255',
            ],
            'mail_password' => [
                'nullable',
                'string',
                'max:255',
            ],
            'mail_encryption' => [
                'nullable',
                'in:tls,ssl,null',
            ],
            'queue_connection' => [
                'required',
                'in:sync,database,redis',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.required'  => 'اسم التطبيق مطلوب',
            'app_url.required'   => 'رابط التطبيق مطلوب',
            'app_url.url'        => 'الرجاء إدخال رابط صحيح (مثل https://beza.app)',
            'app_env.required'   => 'بيئة التشغيل مطلوبة',
            'app_env.in'         => 'بيئة التشغيل يجب أن تكون local أو staging أو production',
            'redis_host.required' => 'مضيف Redis مطلوب',
            'redis_port.required' => 'منفذ Redis مطلوب',
            'queue_connection.required' => 'نظام الطوابور مطلوب',
            'queue_connection.in' => 'نظام الطوابور يجب أن يكون sync أو database أو redis',
        ];
    }
}
```

## Form Request — AdminUserRequest (إنشاء المشرف)

```php
<?php
// app/Http/Requests/Install/AdminUserRequest.php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return env('INSTALLER_LOCKED') !== true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{8}$/',
                Rule::unique('users', 'phone'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'اسم المشرف مطلوب',
            'email.required'      => 'البريد الإلكتروني مطلوب',
            'email.email'         => 'البريد الإلكتروني غير صحيح',
            'email.unique'        => 'البريد الإلكتروني مسجل مسبقاً',
            'phone.required'      => 'رقم الهاتف مطلوب',
            'phone.regex'         => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام',
            'phone.unique'        => 'رقم الهاتف مسجل مسبقاً',
            'password.required'   => 'كلمة المرور مطلوبة',
            'password.min'        => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `db_host` | required | لا يمكن الاتصال بدون مضيف |
| `db_port` | integer / min:1 / max:65535 | منفذ TCP صحيح |
| `db_database` | regex:/^[a-zA-Z0-9_]+$/ | أسماء قواعد بيانات MySQL الآمنة |
| `app_name` | required | يستخدم لعنوان التطبيق واسم الموقع |
| `app_url` | url | يستخدم لإنشاء الروابط في الإيميلات |
| `app_env` | in:local,staging,production | بيئة تشغيل معروفة |
| `email` | email / unique | بريد صحيح وغير مكرر للمشرف |
| `password` | min:8 / confirmed | أمان أساسي للحساب الأول |
