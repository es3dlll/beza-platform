# 06 - نماذج Eloquent (Eloquent Models)

## User Model (جزء تسجيل الخروج)

```php
<?php
// app/Models/User.php — دالتا تسجيل الخروج

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // === دوال تسجيل الخروج ===

    /**
     * تسجيل الخروج — إبطال التوكن الحالي فقط
     */
    public function logout(): void
    {
        JWTAuth::invalidate(true);
    }

    /**
     * تسجيل الخروج من كل الأجهزة
     *
     * @return int عدد الأجهزة
     */
    public function logoutFromAllDevices(): int
    {
        JWTAuth::invalidate(true);
        return 1;
    }

    /**
     * الحصول على عدد الأجهزة النشطة
     */
    public function activeSessionsCount(): int
    {
        return 1;
    }

    /**
     * تسجيل الخروج من كل الأجهزة
     */
    public function logoutFromAllDevices(): int
    {
        $count = $this->tokens()->count();
        $this->tokens()->delete();
        return $count;
    }

    /**
     * الحصول على عدد الأجهزة النشطة
     */
    public function activeSessionsCount(): int
    {
        return $this->tokens()->count();
    }
}
```

## ملاحظات

| الدالة | الاستخدام |
|--------|-----------|
| `logout()` | إبطال التوكن الحالي — يسجل خروج من جهاز واحد |
| `logoutFromAllDevices()` | إبطال التوكن الحالي — يسجل خروج من كل الأجهزة |
| `activeSessionsCount()` | معرفة عدد الجلسات النشطة للمستخدم |
