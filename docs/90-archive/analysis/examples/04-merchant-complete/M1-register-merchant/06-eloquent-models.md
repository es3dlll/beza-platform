# 06 - نماذج Eloquent (Eloquent Models)

## Merchant Model
```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $fillable = [
        'user_id', 'business_name', 'business_type',
        'commercial_registration', 'tax_id', 'owner_phone',
        'owner_name', 'bank_account_info', 'status',
        'fee_percentage', 'rejection_reason', 'approved_at',
    ];
    protected $casts = [
        'bank_account_info' => 'json',
        'fee_percentage'    => 'decimal:2',
        'approved_at'       => 'datetime',
    ];
    public function user() { return $this->belongsTo(User::class); }
    public function documents() { return $this->hasMany(MerchantDocument::class); }
    public function wallets() { return $this->hasMany(MerchantWallet::class); }
    public function approve(): void { $this->update(['status' => 'active', 'approved_at' => now()]); }
}
```

## MerchantWallet Model
```php
<?php
namespace App\Models;
class MerchantWallet extends Model
{
    protected $fillable = ['merchant_id', 'currency', 'wallet_number', 'balance', 'frozen_balance', 'is_active'];
    protected $casts = ['balance' => 'decimal:2', 'frozen_balance' => 'decimal:2', 'is_active' => 'boolean'];
    protected $appends = ['available_balance'];
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function getAvailableBalanceAttribute(): float { return $this->balance - $this->frozen_balance; }
}
```
