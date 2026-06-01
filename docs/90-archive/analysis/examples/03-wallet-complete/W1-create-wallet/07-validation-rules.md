# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## عملية إنشاء المحفظة تلقائية — لا يوجد API مخصص

إنشاء المحفظة يتم تلقائياً عبر `User::created` event، لذلك لا يوجد Form Request مخصص. لكن يجب التحقق من صحة بيانات **التسجيل** التي تؤدي إلى إنشاء المحفظة.

## RegisterRequest (المستخدم)

```php
<?php
// app/Http/Requests/RegisterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9+\-\(\)\s]{7,20}$/',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
            ],
            'pin_code' => [
                'required',
                'string',
                'digits:4',
            ],
            'fcm_token' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'الاسم مطلوب',
            'name.min'            => 'الاسم قصير جداً (حرفان على الأقل)',
            'name.max'            => 'الاسم طويل جداً',
            'phone.required'      => 'رقم الهاتف مطلوب',
            'phone.unique'        => 'رقم الهاتف مسجل مسبقاً',
            'phone.regex'         => 'صيغة رقم الهاتف غير صحيحة',
            'password.required'   => 'كلمة المرور مطلوبة',
            'password.min'        => 'كلمة المرور قصيرة جداً (8 أحرف على الأقل)',
            'pin_code.required'   => 'رمز PIN مطلوب',
            'pin_code.digits'     => 'PIN يجب أن يكون 4 أرقام',
        ];
    }
}
```

## التحقق الداخلي (في CreateWalletService)

هذه التحققات تتم داخل الـ Service لأنها Business Rules:

```php
// في CreateWalletService

// 1. التحقق من أن المستخدم نشط
if ($user->status !== 'active') {
    throw new UserNotActiveException('لا يمكن إنشاء محفظة لمستخدم غير نشط');
}

// 2. التحقق من عدم وجود محافظ مسبقة
if ($user->wallets()->exists()) {
    throw new WalletsAlreadyExistException('المستخدم لديه محافظ مسبقاً');
}

// 3. التحقق من تفرد wallet_number
$sypNumber = $this->generateWalletNumber('SYP');
$usdNumber = $this->generateWalletNumber('USD');
// do-while loop يضمن عدم التكرار

// 4. التأكد من أن balance >= 0
// balance يبدأ بـ 0.00 في MySQL
```

## سبب كل قاعدة (داخلية)

| القاعدة | السبب |
|---------|-------|
| عدم وجود محافظ مسبقة | منع تكرار الإنشاء إذا انطلق الحدث أكثر من مرة |
| المستخدم نشط | المحفظة تحتاج مستخدم نشط لتفعيلها |
| wallet_number فريد | كل محفظة يجب أن يكون لها رقم فريد لاستخدامه في التحويلات |
| بادئة 62 لـ SYP | تمييز بصري للمستخدم — أرقام المحافظ تبدأ برمز العملة |
| بادئة 63 لـ USD | تمييز بصري — 62 = سورية، 63 = دولار (ترتيب الحروف) |
| رصيد أولي 0 | لا يمكن بدء برصيد سالب |
| هدية 5 USD كاملة | مبلغ ثابت تشجيعي يُسجل كـ deposit |

## ملخص كل أنواع التحقق

| النوع | أين يتم | الترتيب |
|-------|---------|---------|
| Structural validation | RegisterRequest (rules) | 1 |
| Business validation | CreateWalletService | 2 |
| Database constraints | MySQL (UNIQUE, FK) | 3 |
| Atomic safety | DB::transaction | 4 |
