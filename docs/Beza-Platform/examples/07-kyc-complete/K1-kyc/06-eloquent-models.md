# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## KycDocument Model

```php
<?php
// app/Models/KycDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
    protected $fillable = [
        'user_id', 'doc_type', 'doc_category',
        'file_path', 'file_hash', 'mime_type',
        'auto_verified', 'auto_rejection_reason',
    ];

    protected $casts = [
        'auto_verified' => 'boolean',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('doc_category', $category);
    }
}
```

## KycReview Model

```php
<?php
// app/Models/KycReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycReview extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'reviewed_by', 'status', 'notes', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
```

## User Model (إضافة KYC)

```php
<?php
// app/Models/User.php — إضافات KYC

class User extends Authenticatable
{
    // ...

    protected $casts = [
        // ... القائمة الموجودة +
        'kyc_verified_at' => 'datetime',
    ];

    public function kycDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function kycReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KycReview::class);
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    /**
     * هل تجاوز حد 100 USD بدون KYC؟
     */
    public function hasExceededKycLimit(): bool
    {
        if ($this->isKycVerified()) return false;

        $totalBalance = $this->wallets()->sum('balance');
        return $totalBalance >= 100; // USD
    }
}
```
