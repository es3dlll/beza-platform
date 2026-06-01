# 06 - الموديلز مع العلاقات (Eloquent Models)

## User Model

```php
class User extends Authenticatable implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use Notifiable, SoftDeletes;

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected $fillable = [
        'uuid', 'name', 'email', 'phone', 'password', 'pin_code',
        'status', 'kyc_status', 'is_admin', 'is_merchant', 'is_agent',
        'device_id', 'fcm_token', 'last_login_ip', 'preferences',
    ];

    protected $hidden = ['password', 'pin_code', 'two_factor_secret'];

    protected $casts = [
        'is_admin' => 'boolean', 'is_merchant' => 'boolean',
        'is_agent' => 'boolean', 'preferences' => 'json',
    ];

    public function wallets(): HasMany { return $this->hasMany(Wallet::class); }
    public function sypWallet(): HasOne { return $this->hasOne(Wallet::class)->where('currency', 'SYP'); }
    public function usdWallet(): HasOne { return $this->hasOne(Wallet::class)->where('currency', 'USD'); }
    public function merchant(): HasOne { return $this->hasOne(Merchant::class); }
    public function agent(): HasOne { return $this->hasOne(Agent::class); }
    public function kycDocuments(): HasMany { return $this->hasMany(KycDocument::class); }
}
```

## Wallet Model

```php
class Wallet extends Model
{
    protected $fillable = ['user_id', 'currency', 'wallet_number', 'balance', 'frozen_balance', 'is_active'];
    protected $casts = ['balance' => 'decimal:2', 'frozen_balance' => 'decimal:2', 'is_active' => 'boolean'];
    protected $appends = ['available_balance'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function sentTransactions(): HasMany { return $this->hasMany(Transaction::class, 'from_wallet_id'); }
    public function receivedTransactions(): HasMany { return $this->hasMany(Transaction::class, 'to_wallet_id'); }
    public function getAvailableBalanceAttribute(): float { return $this->balance - $this->frozen_balance; }
}
```

## Transaction Model

```php
class Transaction extends Model
{
    protected $fillable = ['from_wallet_id', 'to_wallet_id', 'amount', 'amount_in_usd', 'type', 'status', 'reference_number', 'description', 'fee', 'metadata', 'completed_at'];
    protected $casts = ['amount' => 'decimal:2', 'amount_in_usd' => 'decimal:2', 'fee' => 'decimal:2', 'metadata' => 'json', 'completed_at' => 'datetime'];

    public function fromWallet(): BelongsTo { return $this->belongsTo(Wallet::class, 'from_wallet_id'); }
    public function toWallet(): BelongsTo { return $this->belongsTo(Wallet::class, 'to_wallet_id'); }
    public static function generateReferenceNumber(): string { return 'BZ' . now()->format('ymdHis') . strtoupper(substr(uniqid(), -6)); }
}
```
