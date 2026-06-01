# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## استثناءات الإحالة

```php
<?php
// app/Exceptions/SelfReferralException.php

namespace App\Exceptions;

use Exception;

class SelfReferralException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكنك دعوة نفسك',
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/AlreadyReferredException.php

class AlreadyReferredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لقد تمت دعوتك بالفعل من قبل مستخدم آخر',
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/DuplicateReferralException.php

class DuplicateReferralException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تم تسجيل هذه الدعوة مسبقاً',
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | SelfReferralException | لا يمكنك دعوة نفسك |
| 422 | AlreadyReferredException | تمت دعوتك بالفعل |
| 422 | DuplicateReferralException | دعوة مكررة |
| 422 | ValidationException | كود غير صحيح |
