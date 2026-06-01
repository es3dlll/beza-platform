# 07 - قواعد التحقق (Validation Rules)

## لا يوجد Form Request للـ Logout

عملية تسجيل الخروج لا تحتاج إدخال بيانات — فقط التوكن في Header.

```php
// routes/api.php
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:api');
```

## التحقق الوحيد — وجود التوكن

```php
<?php
// في AuthController@logout

public function logout(Request $request): JsonResponse
{
    // auth:api middleware يضمن وجود توكن صالح
    JWTAuth::invalidate(true);

    return response()->json([
        'success' => true,
        'message' => 'تم تسجيل الخروج بنجاح',
    ]);
}
```

## سبب عدم الحاجة لـ Form Request

| السبب | التفصيل |
|-------|---------|
| لا توجد بيانات إدخال | فقط Header (Authorization) |
| المصادقة مضمونة | middleware `auth:api` |
| عملية بسيطة | حذف سجل واحد فقط |
