# 06 - الموديلز (Eloquent Models)

## Merchant Model

```php
<?php
// app/Models/Merchant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'business_type',
        'commercial_reg_no', 'tax_card_no', 'address',
        'website', 'description', 'status',
        'rejection_reason', 'reviewed_by', 'reviewed_at',
        'total_transactions', 'total_volume',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'total_volume' => 'decimal:2',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MerchantDocument::class);
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function approve(int $reviewerId): void
    {
        $this->update([
            'status'      => 'active',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
        $this->user->update(['is_merchant' => true]);
    }

    public function reject(string $reason, int $reviewerId): void
    {
        $this->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => now(),
        ]);
    }
}
```

## MerchantDocument Model

```php
<?php
// app/Models/MerchantDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantDocument extends Model
{
    protected $fillable = [
        'merchant_id', 'type', 'file_path',
        'original_name', 'status', 'notes',
    ];

    public function merchant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
```

## Agent Model

```php
<?php
// app/Models/Agent.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id', 'office_name', 'license_number',
        'address', 'service_areas', 'status',
        'rejection_reason', 'reviewed_by', 'reviewed_at',
        'total_transactions', 'total_commission',
    ];

    protected $casts = [
        'service_areas' => 'json',
        'reviewed_at' => 'datetime',
        'total_commission' => 'decimal:2',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function approve(int $reviewerId): void
    {
        $this->update([
            'status'      => 'active',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
        $this->user->update(['is_agent' => true]);
    }

    public function reject(string $reason, int $reviewerId): void
    {
        $this->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => now(),
        ]);
    }
}
```
