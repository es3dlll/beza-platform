# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## ReferralCode Model

```php
<?php
// app/Models/ReferralCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $fillable = ['user_id', 'code', 'is_active', 'usage_count'];

    protected $casts = [
        'is_active'    => 'boolean',
        'usage_count'  => 'integer',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rewards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
```

## ReferralReward Model

```php
<?php
// app/Models/ReferralReward.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    protected $fillable = [
        'referrer_id', 'referred_id', 'referral_code_id',
        'reward_type', 'referrer_amount', 'referred_amount',
        'status', 'trigger_transaction_id',
    ];

    protected $casts = [
        'referrer_amount' => 'decimal:2',
        'referred_amount' => 'decimal:2',
    ];

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function referralCode(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function triggerTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'trigger_transaction_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
```

## User Model (إضافة referral)

```php
<?php
// app/Models/User.php — إضافات referral

class User extends Authenticatable
{
    // ...

    public function referralCode(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReferralCode::class);
    }

    public function referredBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referralRewardsGiven(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReferralReward::class, 'referrer_id');
    }

    public function referralRewardsReceived(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReferralReward::class, 'referred_id');
    }
}
```
