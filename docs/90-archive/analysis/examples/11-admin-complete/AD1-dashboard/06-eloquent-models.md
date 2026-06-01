# 06 - الموديلز مع العلاقات للوحة التحكم (Eloquent Models)

لوحة التحكم تستخدم الموديلز الموجودة: User, Wallet, Transaction Merchant, Agent.

## موديلات إضافية للتتبع

### DashboardCache Model

```php
<?php
// app/Models/Admin/DashboardCache.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class DashboardCache extends Model
{
    protected $table = 'dashboard_cache';

    protected $fillable = [
        'key', 'data', 'expires_at',
    ];

    protected $casts = [
        'data'       => 'json',
        'expires_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public static function isExpired(string $key): bool
    {
        $cache = self::where('key', $key)->valid()->first();
        return $cache === null;
    }
}
```

### DailyActiveUsersLog Model

```php
<?php
// app/Models/Admin/DailyActiveUsersLog.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class DailyActiveUsersLog extends Model
{
    protected $table = 'daily_active_users_log';

    protected $fillable = [
        'date', 'active_count', 'new_registrations',
        'transaction_count', 'transaction_volume', 'breakdown',
    ];

    protected $casts = [
        'date'               => 'date',
        'breakdown'          => 'json',
        'transaction_volume' => 'decimal:2',
    ];

    public function scopeLastDays($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days))
            ->orderBy('date');
    }
}
```

## العلاقات المضافة في User للتجميع

```php
// إضافات إلى app/Models/User.php

// عدد معاملات المستخدم (للتقرير)
public function transactionsCount(): int
{
    return $this->hasManyThrough(
        Transaction::class, Wallet::class,
        'user_id', 'from_wallet_id'
    )->count();
}

// إجمالي حجم معاملات المستخدم
public function transactionsVolume(): float
{
    return (float) $this->hasManyThrough(
        Transaction::class, Wallet::class,
        'user_id', 'from_wallet_id'
    )->where('status', 'completed')->sum('amount');
}
```
