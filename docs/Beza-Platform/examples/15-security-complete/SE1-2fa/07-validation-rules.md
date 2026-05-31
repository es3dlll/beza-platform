# 07 - قواعد التحقق من 2FA (Validation Rules)

## Enable 2FA Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
```

## Verify 2FA Code Request

```php
class VerifyTwoFactorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز التحقق مطلوب',
            'code.size' => 'رمز التحقق يجب أن يكون 6 أرقام',
        ];
    }
}
```

## Two Factor Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled()) {
            $code = $request->header('X-2FA-Code') ?? $request->input('two_factor_code');

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'مطلوب رمز المصادقة الثنائية',
                    'requires_2fa' => true,
                ], 402); // 402 = Payment Required (قريب من المعنى)
            }

            $valid = app(TwoFactorService::class)->verifyCode(
                $user->twoFactorSecret(),
                $code
            );

            if (!$valid) {
                // التحقق من رموز الاسترداد
                $valid = app(TwoFactorService::class)->useRecoveryCode($user, $code);
            }

            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'رمز المصادقة الثنائية غير صحيح',
                ], 422);
            }
        }

        return $next($request);
    }
}
```

## تسجيل Middleware

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    '2fa' => \App\Http\Middleware\RequireTwoFactor::class,
];

// في مسارات API
Route::middleware(['auth:api', '2fa'])->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
    Route::post('/wallet/exchange', [WalletController::class, 'exchange']);
});
```
