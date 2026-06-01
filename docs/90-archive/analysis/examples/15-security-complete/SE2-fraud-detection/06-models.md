# 06 - موديلات نظام كشف الاحتيال (Models)

## FlaggedTransaction Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlaggedTransaction extends Model
{
    protected $fillable = [
        'transaction_id', 'user_id', 'amount', 'currency',
        'triggered_rules', 'risk_score', 'status', 'notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'triggered_rules' => 'array',
        'risk_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'notes' => $reason,
        ]);

        // إلغاء المعاملة إذا كانت معلقة
        if ($this->transaction && $this->transaction->status === 'pending') {
            $this->transaction->markFailed($reason);
        }
    }
}
```

## BlockedIp Model

```php
class BlockedIp extends Model
{
    protected $fillable = ['ip', 'reason', 'is_active', 'blocked_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public static function isBlocked(string $ip): bool
    {
        return static::where('ip', $ip)->where('is_active', true)->exists();
    }
}
```
