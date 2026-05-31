# 13 - الاستثناءات (Exception Handling)

```php
<?php
// app/Exceptions/Admin/ApplicationAlreadyProcessedException.php
class ApplicationAlreadyProcessedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تمت معالجة هذا الطلب مسبقاً',
        ], 422);
    }
}

// app/Exceptions/Admin/DocumentsNotReviewedException.php
class DocumentsNotReviewedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'يجب مراجعة جميع المستندات أولاً',
        ], 422);
    }
}

// app/Exceptions/Admin/KycNotVerifiedException.php
class KycNotVerifiedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'التحقق من هوية المستخدم (KYC) لم يكتمل بعد',
        ], 422);
    }
}

// app/Exceptions/Admin/DuplicateMerchantException.php
class DuplicateMerchantException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'يوجد تاجر مسجل بهذا السجل التجاري مسبقاً',
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 404 | ModelNotFoundException | الطلب غير موجود |
| 422 | ApplicationAlreadyProcessedException | تمت المعالجة مسبقاً |
| 422 | DocumentsNotReviewedException | المستندات غير مكتملة |
| 422 | KycNotVerifiedException | KYC لم يكتمل |
| 422 | DuplicateMerchantException | سجل تجاري مكرر |
| 403 | UnauthorizedAdminException | صلاحية مشرف مطلوبة |
