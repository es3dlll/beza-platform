# 06 - موديل AuditLog (Eloquent Model)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'event_type', 'loggable_type', 'loggable_id',
        'user_id', 'data', 'ip', 'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // === Helpers ===

    public static function log(
        string $eventType,
        ?Model $loggable,
        ?User $user,
        array $data = [],
        ?string $ip = null,
        ?string $userAgent = null,
    ): self {
        return static::create([
            'event_type' => $eventType,
            'loggable_type' => $loggable ? get_class($loggable) : null,
            'loggable_id' => $loggable?->getKey(),
            'user_id' => $user?->id,
            'data' => $data,
            'ip' => $ip ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    public function getEventLabel(): string
    {
        return match ($this->event_type) {
            'login' => 'تسجيل دخول',
            'transfer_created' => 'تحويل',
            'pin_changed' => 'تغيير PIN',
            'kyc_verified' => 'توثيق KYC',
            default => $this->event_type,
        };
    }
}
```
