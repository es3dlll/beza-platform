# 06 - نماذج Eloquent (Eloquent Models)

## PaymentLink Model
```php
<?php
namespace AppModels;
use IlluminateDatabaseEloquentModel;

class PaymentLink extends Model
{
    protected $fillable = ['merchant_id', 'token', 'amount', 'currency', 'description', 'redirect_url', 'status', 'expires_at', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];

    public function merchant() { return $this->belongsTo(Merchant::class); }

    public function isExpired(): bool {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function isActive(): bool {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function markAsPaid(): void {
        $this->update(['status' => 'used', 'paid_at' => now()]);
    }

    public function markAsExpired(): void {
        $this->update(['status' => 'expired']);
    }

    public function cancel(): void {
        $this->update(['status' => 'cancelled']);
    }

    public static function generateToken(): string {
        return bin2hex(random_bytes(32));
    }

    public function scopeActive($q) { return $q->where('status', 'active')->where('expires_at', '>', now()); }
    public function scopeByMerchant($q, $merchantId) { return $q->where('merchant_id', $merchantId); }
}
```

## شرح الموديل
- isExpired(): التحقق من انتهاء الصلاحية بناءً على الحالة أو الوقت
- isActive(): التحقق من صلاحية الرابط للاستخدام
- markAsPaid(): تحديث الحالة بعد الدفع
- generateToken(): إنشاء توكن عشوائي آمن (64 حرف hex = 256 bit)
- scopes: active, byMerchant للاستعلامات السريعة
