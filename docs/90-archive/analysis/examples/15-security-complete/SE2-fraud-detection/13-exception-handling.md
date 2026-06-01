# 13 - استثناءات الاحتيال (Exception Handling)

## TransactionBlockedByFraudDetectionException

```php
<?php

namespace App\Exceptions;

use Exception;

class TransactionBlockedByFraudDetectionException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تم حظر المعاملة مؤقتاً للمراجعة',
            'errors' => [
                'fraud' => ['تم اكتشاف نشاط غير معتاد. تم تعليق المعاملة للمراجعة.'],
            ],
        ], 403);
    }
}
```

## PinLockoutException

```php
class PinLockoutException extends Exception
{
    public function __construct(private int $remainingMinutes)
    {
        parent::__construct("PIN مقفول لمدة {$remainingMinutes} دقيقة");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'PIN مقفول مؤقتاً',
            'errors' => [
                'pin' => ["تم قفل PIN لمدة {$this->remainingMinutes} دقيقة بسبب محاولات خاطئة."],
            ],
        ], 429);
    }
}
```

## DeviceNotTrustedException

```php
class DeviceNotTrustedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'جهاز غير موثوق',
            'requires_verification' => true,
            'errors' => [
                'device' => ['هذا الجهاز غير معروف. يرجى التحقق عبر OTP.'],
            ],
        ], 403);
    }
}

class IpBlockedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تم حظر عنوان IP الخاص بك',
        ], 403);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 403 | TransactionBlockedByFraudDetectionException | تم حظر المعاملة للمراجعة |
| 429 | PinLockoutException | PIN مقفول مؤقتاً |
| 403 | DeviceNotTrustedException | جهاز غير موثوق |
| 403 | IpBlockedException | تم حظر IP الخاص بك |
