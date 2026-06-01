# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

_(نفس موديل D1 مع دوال الإلغاء)_

## Deal Model (إضافات للإلغاء)

```php
<?php
// app/Models/Deal.php — دوال الإلغاء

class Deal extends Model
{
    // ...

    /**
     * هل يمكن إلغاء الصفقة؟
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * إلغاء الصفقة
     */
    public function markAsCancelled(string $reason): void
    {
        $this->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);
    }

    /**
     * عدد المستثمرين النشطين
     */
    public function activeInvestorsCount(): int
    {
        return $this->investments()->where('status', 'active')->count();
    }

    /**
     * إجمالي مبلغ الاستثمارات النشطة
     */
    public function activeInvestmentsTotal(): float
    {
        return (float) $this->investments()
            ->where('status', 'active')
            ->sum('amount');
    }
}
```
