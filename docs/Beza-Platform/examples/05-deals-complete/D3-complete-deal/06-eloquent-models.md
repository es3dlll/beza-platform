# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

_(نفس موديل D1 مع دوال التوزيع)_

## Deal Model (إضافات لإتمام الصفقة)

```php
<?php
// app/Models/Deal.php — دوال الإنهاء

class Deal extends Model
{
    // ...

    /**
     * حساب إجمالي الربح الفعلي
     */
    public function calculateTotalProfit(): float
    {
        return round($this->target_amount * ($this->profit_actual / 100), 2);
    }

    /**
     * هل يمكن إتمام الصفقة؟
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['active', 'filled']);
    }

    /**
     * إنهاء الصفقة
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }
}
```

## DealInvestment Model (إضافات للأرباح)

```php
<?php
// app/Models/DealInvestment.php — دوال الربح

class DealInvestment extends Model
{
    // ...

    /**
     * حساب حصة الربح لهذا المستثمر
     */
    public function calculateProfitShare(float $totalProfit, float $totalInvested): float
    {
        if ($totalInvested <= 0) return 0;
        $ratio = $this->amount / $totalInvested;
        return round($ratio * $totalProfit, 2);
    }
}
```
