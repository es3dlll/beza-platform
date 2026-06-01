# 06 - نماذج Eloquent للتسوية

## نظرة عامة
نماذج Eloquent تمثل جداول التسوية في قاعدة البيانات وتوفر واجهة برمجية للتعامل معها. تشمل العلاقات، النطاقات، والتحويلات (Casts).

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantSettlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'period_start',
        'period_end',
        'gross_amount',
        'commission_percentage',
        'commission_amount',
        'transfer_fee',
        'refunds_deducted',
        'chargebacks_deducted',
        'net_amount',
        'currency',
        'bank_name',
        'bank_account_number',
        'iban',
        'swift_code',
        'bank_transaction_ref',
        'failure_reason',
        'status',
        'settlement_date',
        'bank_transfer_initiated_at',
        'bank_transfer_completed_at',
    ];

    protected $casts = [
        // تحويل القيم المالية إلى decimal
        'gross_amount'           => 'decimal:2',
        'commission_percentage'  => 'decimal:2',
        'commission_amount'      => 'decimal:2',
        'transfer_fee'           => 'decimal:2',
        'refunds_deducted'       => 'decimal:2',
        'chargebacks_deducted'   => 'decimal:2',
        'net_amount'             => 'decimal:2',

        // تحويل التواريخ
        'period_start'               => 'date:Y-m-d',
        'period_end'                 => 'date:Y-m-d',
        'settlement_date'            => 'datetime:Y-m-d H:i:s',
        'bank_transfer_initiated_at' => 'datetime:Y-m-d H:i:s',
        'bank_transfer_completed_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at'                 => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = [
        'total_deductions',
        'net_percentage',
        'status_label',
    ];

    // ===== العلاقات =====

    /**
     * العلاقة مع التاجر (التابع لـ)
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * بنود التسوية (تفاصيل المعاملات المكونة لها)
     */
    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    // ===== النطاقات (Scopes) =====

    /**
     * نطاق: طلبات التسوية المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * نطاق: طلبات التسوية قيد المعالجة
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * نطاق: طلبات التسوية المنجزة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * نطاق: طلبات التسوية الفاشلة
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * نطاق: طلبات التسوية في فترة زمنية محددة
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                     ->where('period_end', '<=', $endDate);
    }

    /**
     * نطاق: طلبات التسوية بعملة محددة
     */
    public function scopeCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * نطاق: طلبات التسوية التي تحتاج إعادة محاولة
     */
    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'failed')
                     ->where('created_at', '>=', now()->subDays(3));
    }

    // ===== الخصائص المحسوبة (Accessors) =====

    /**
     * إجمالي الخصومات
     */
    public function getTotalDeductionsAttribute(): float
    {
        return $this->commission_amount
             + $this->transfer_fee
             + $this->refunds_deducted
             + $this->chargebacks_deducted;
    }

    /**
     * النسبة المئوية للصافي من الإجمالي
     */
    public function getNetPercentageAttribute(): float
    {
        if ($this->gross_amount > 0) {
            return round(($this->net_amount / $this->gross_amount) * 100, 2);
        }
        return 0;
    }

    /**
     * تسمية الحالة بالعربية
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'             => 'قيد الانتظار',
            'processing'          => 'قيد المعالجة',
            'completed'           => 'تم بنجاح',
            'failed'              => 'فشل',
            'partially_completed' => 'نجح جزئياً',
            'cancelled'           => 'ملغي',
            default               => $this->status,
        };
    }
}
```

## نموذج SettlementItem

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id',
        'sourceable_id',
        'sourceable_type',
        'amount',
        'type',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * العلاقة مع التسوية الرئيسية
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(MerchantSettlement::class);
    }

    /**
     * العلاقة متعددة الأشكال مع المصدر (عملية دفع، مرتجع، إلخ)
     */
    public function sourceable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
```

## نموذج MerchantBankAccount

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantBankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'iban',
        'swift_code',
        'currency',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * تعيين هذا الحساب كحساب افتراضي
     */
    public function markAsDefault(): void
    {
        self::where('merchant_id', $this->merchant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
```
