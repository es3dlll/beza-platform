# Controllers — WAP

**مبدأ إعادة الاستخدام:** Controllers WAP تستدعي نفس Services الموجودة في `app/Modules/`.

```php
// App\Http\Controllers\Api\Wap\AuthController
class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. تحقق من المدخلات
        // 2. استدعاء AuthService::login($credentials)
        // 3. إنشاء JWT + Refresh Token
        // 4. ضبط Cookie (HttpOnly, Secure, SameSite=Strict)
        // 5. إرجاع رد مع Set-Cookie headers
    }

    public function me(Request $request)
    {
        // إرجاع معلومات المستخدم الحالي
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->only(['id', 'name', 'email', 'phone', 'role']),
                'wallets' => $request->user()->wallets,
            ]
        ]);
    }
}
```

> **هام:** لا تنشئ Services جديدة. استخدم `app/Modules/Auth/Services/AuthService.php` وغيره.
