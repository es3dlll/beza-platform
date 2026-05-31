# 06 - الموديلز

```php
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MerchantSubscription extends Model
{
    protected $fillable = ['merchant_id', 'customer_id', 'amount', 'currency', 'interval', 'status', 'max_cycles', 'current_cycle', 'next_charge_at', 'customer_consented_at'];
    protected $casts = ['amount' => 'decimal:2', 'max_cycles' => 'integer', 'current_cycle' => 'integer', 'next_charge_at' => 'datetime', 'customer_consented_at' => 'datetime'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function charges() { return $this->hasMany(SubscriptionCharge::class); }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isComplete(): bool { return $this->current_cycle >= $this->max_cycles; }
}

class SubscriptionCharge extends Model
{
    protected $fillable = ['subscription_id', 'cycle_number', 'amount', 'status', 'charged_at'];
    protected $casts = ['amount' => 'decimal:2'];
    public function subscription() { return $this->belongsTo(MerchantSubscription::class); }
}
```
