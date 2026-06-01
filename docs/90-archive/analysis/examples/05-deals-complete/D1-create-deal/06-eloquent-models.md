# 06 - الموديلز مع العلاقات والـ Casts (Eloquent Models)

## Deal Model

```php
<?php
// app/Models/Deal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = [
        'created_by', 'title', 'description', 'target_amount',
        'current_amount', 'currency', 'expected_profit_percentage',
        'profit_actual', 'duration_days', 'category', 'risk_level',
        'status', 'cancellation_reason', 'starts_at',
        'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'target_amount'              => 'decimal:2',
        'current_amount'             => 'decimal:2',
        'expected_profit_percentage' => 'decimal:2',
        'profit_actual'              => 'decimal:2',
        'duration_days'              => 'integer',
        'starts_at'                  => 'datetime',
        'completed_at'               => 'datetime',
        'cancelled_at'               => 'datetime',
    ];

    protected $appends = ['remaining_amount', 'progress_percentage'];

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function investments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DealInvestment::class);
    }

    public function investors(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(User::class, DealInvestment::class,
            'deal_id', 'id', 'id', 'investor_id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return min(100, round(($this->current_amount / $this->target_amount) * 100, 2));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'filled']);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->whereColumn('current_amount', '<', 'target_amount');
    }
}
```

## DealInvestment Model

```php
<?php
// app/Models/DealInvestment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealInvestment extends Model
{
    protected $fillable = [
        'deal_id', 'investor_id', 'amount', 'amount_in_usd',
        'currency', 'profit_earned', 'status', 'refunded_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'amount_in_usd' => 'decimal:2',
        'profit_earned' => 'decimal:2',
        'refunded_at'   => 'datetime',
    ];

    public function deal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function investor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```
