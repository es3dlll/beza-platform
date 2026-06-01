# 09 - سيرفس لير العملية — AuthService (Logout)

```php
<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * تسجيل الخروج — إبطال التوكن الحالي
     */
    public function logout(User $user): void
    {
        JWTAuth::invalidate(true);

        Log::info('تسجيل خروج', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * تسجيل الخروج من كل الأجهزة
     *
     * @return int عدد الأجهزة
     */
    public function logoutFromAllDevices(User $user): int
    {
        JWTAuth::invalidate(true);

        Log::info('تسجيل خروج من كل الأجهزة', [
            'user_id' => $user->id,
        ]);

        return 1;
    }
}
```

## تدفق logout()

```
logout():
1. Invalidate current JWT token
2. Log action

logoutAll():
1. Invalidate current JWT token
2. Return 1
```
