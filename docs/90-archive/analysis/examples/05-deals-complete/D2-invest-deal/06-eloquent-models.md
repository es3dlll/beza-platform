# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

_(نفس موديل D1 — يركز على دوال الاستثمار)_

## Deal Model (إضافات للاستثمار)

```php
<?php
// app/Models/Deal.php — دوال إضافية

class Deal extends Model
{
    // ...

    /**
     * هل الصفقة متاحة للاستثمار؟
     */
    public function isAvailableForInvestment(): bool
    {
        return in_array($this->status, ['active', 'filled'])
            && $this->current_amount < $this->target_amount;
    }

    /**
     * المبلغ المتبقي المطلوب
     */
    public function getRemainingNeededAmount(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    /**
     * هل المبلغ المطلوب يتجاوز المتبقي؟
     */
    public function exceedsRemaining(float $amount): bool
    {
        return ($this->current_amount + $amount) > $this->target_amount;
    }

    /**
     * زيادة current_amount بشكل ذري
     */
    public function incrementCurrentAmount(float $amount): bool
    {
        return (bool) DB::update(
            'UPDATE deals SET current_amount = current_amount + ?
             WHERE id = ? AND (current_amount + ?) <= target_amount',
            [$amount, $this->id, $amount]
        );
    }
}
```

## DealInvestment Model (نفس D1)

```php
<?php
// app/Models/DealInvestment.php — بدون تغيير
```
