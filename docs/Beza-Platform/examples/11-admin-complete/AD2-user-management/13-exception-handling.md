# 13 - كل الاستثناءات ومعالجتها

## Custom Exceptions

```php
<?php
// app/Exceptions/Admin/CannotDeleteSelfException.php
class CannotDeleteSelfException extends Exception
{
    public function __construct()
    {
        parent::__construct('لا يمكن حذف حسابك الشخصي');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن حذف حسابك الشخصي',
        ], 422);
    }
}

// app/Exceptions/Admin/CannotBlockAdminException.php
class CannotBlockAdminException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'لا يمكن حظر مشرف',
        ], 422);
    }
}

// app/Exceptions/Admin/UserNotFoundException.php
class UserNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('المستخدم غير موجود');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المستخدم غير موجود',
        ], 404);
    }
}

// app/Exceptions/Admin/UserAlreadySuspendedException.php
class UserAlreadySuspendedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المستخدم معلق بالفعل',
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 404 | UserNotFoundException | المستخدم غير موجود |
| 422 | CannotDeleteSelfException | لا يمكن حذف حسابك الشخصي |
| 422 | CannotBlockAdminException | لا يمكن حظر مشرف |
| 422 | UserAlreadySuspendedException | المستخدم معلق بالفعل |
| 403 | Unauthorized | صلاحية مشرف مطلوبة |
