# 13 - الاستثناءات (Exception Handling)

```php
<?php
// app/Exceptions/Admin/ReportPeriodTooLongException.php
class ReportPeriodTooLongException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'الفترة الزمنية المحددة طويلة جداً. الحد الأقصى هو 365 يوماً.',
        ], 422);
    }
}

// app/Exceptions/Admin/ReportNotReadyException.php
class ReportNotReadyException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'التقرير لا يزال قيد التوليد. حاول مرة أخرى لاحقاً.',
        ], 202);
    }
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | ReportPeriodTooLongException | الفترة طويلة جداً |
| 202 | ReportNotReadyException | التقرير قيد التوليد |
| 403 | UnauthorizedAdminException | صلاحية مشرف مطلوبة |
