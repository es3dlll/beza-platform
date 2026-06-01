# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## استثناءات KYC

```php
<?php
// app/Exceptions/KycAlreadySubmittedException.php

namespace App\Exceptions;

use Exception;

class KycAlreadySubmittedException extends Exception
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("تم تقديم طلب KYC مسبقاً. الحالة: {$currentStatus}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لديك طلب KYC قيد المعالجة بالفعل',
            'errors'  => [
                'kyc' => ['يمكنك تقديم طلب جديد فقط إذا كانت حالتك not_submitted أو rejected'],
            ],
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/KycLimitExceededException.php

class KycLimitExceededException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لقد تجاوزت حد 100 USD بدون KYC',
            'errors'  => [
                'kyc' => ['يرجى تقديم وثائق KYC لإلغاء الحد'],
            ],
        ], 422);
    }
}
```

## معالجة رفع الملفات

```php
// استثناءات رفع الملفات
try {
    $file->store('kyc/...');
} catch (\Throwable $e) {
    throw new \RuntimeException('فشل رفع الملفات، يرجى المحاولة مرة أخرى');
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | KycAlreadySubmittedException | طلب KYC موجود مسبقاً |
| 422 | KycLimitExceededException | تجاوز حد 100 USD |
| 422 | ValidationException | ملف غير صالح |
| 500 | RuntimeException | فشل رفع الملفات |
