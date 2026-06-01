# 06 - الموديلز (Eloquent Models)

## AdminActivityLog Model

```php
<?php
// app/Models/Admin/AdminActivityLog.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';

    protected $fillable = [
        'admin_id', 'action', 'target_type',
        'target_id', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function admin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }
}
```

## تحسينات User Model للإدارة

```php
// إضافات إلى app/Models/User.php

// Scopes
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeSuspended($query)
{
    return $query->where('status', 'suspended');
}

public function scopeBlocked($query)
{
    return $query->where('status', 'blocked');
}

public function scopeWithKycStatus($query, string $status)
{
    return $query->where('kyc_status', $status);
}

public function scopeSearch($query, string $term)
{
    return $query->where(function ($q) use ($term) {
        $q->where('name', 'like', "%{$term}%")
          ->orWhere('phone', 'like', "%{$term}%")
          ->orWhere('email', 'like', "%{$term}%");
    });
}

// Helpers
public function isActive(): bool
{
    return $this->status === 'active' && $this->deleted_at === null;
}

public function isSuspended(): bool
{
    return $this->status === 'suspended';
}

public function isBlocked(): bool
{
    return $this->status === 'blocked';
}

public function canLogin(): bool
{
    return $this->status === 'active' && $this->deleted_at === null;
}

public function suspend(): void
{
    $this->update(['status' => 'suspended']);
}

public function activate(): void
{
    $this->update(['status' => 'active']);
}

public function block(): void
{
    $this->update(['status' => 'blocked']);
}
```
