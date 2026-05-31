# 13 - الاستثناءات (Exception Handling)

```php
<?php
// app/Exceptions/Admin/DisputeAlreadyResolvedException.php
class DisputeAlreadyResolvedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تم حل هذا النزاع مسبقاً',
        ], 422);
    }
}

// app/Exceptions/Admin/DisputeExpiredException.php
class DisputeExpiredException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'انتهت مدة النزاع (48 ساعة). تم إغلاقه تلقائياً.',
        ], 422);
    }
}

// app/Exceptions/Admin/RefundFailedException.php
class RefundFailedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فشل استرجاع المبلغ. رصيد التاجر غير كافٍ.',
        ], 422);
    }
}
```

## جدول الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | DisputeAlreadyResolvedException | تم حل النزاع مسبقاً |
| 422 | DisputeExpiredException | انتهت مدة النزاع |
| 422 | RefundFailedException | رصيد غير كافٍ للاسترجاع |
| 403 | UnauthorizedException | صلاحية مشرف مطلوبة |
