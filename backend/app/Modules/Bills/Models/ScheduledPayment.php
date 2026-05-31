<?php

declare(strict_types=1);

namespace App\Modules\Bills\Models;

use App\Modules\Bills\Database\Factories\ScheduledPaymentFactory;
use App\Modules\BillProvider\Models\BillProvider;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ScheduledPayment extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'scheduled_payments';

    protected $fillable = [
        'user_id',
        'bill_provider_id',
        'account_number',
        'amount_fils',
        'recurrence',
        'recurrence_day',
        'next_execution_date',
        'last_executed_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'amount_fils' => 'integer',
        'recurrence_day' => 'integer',
        'next_execution_date' => 'date:Y-m-d',
        'last_executed_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(BillProvider::class, 'bill_provider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_execution_date', '<=', now()->toDateString());
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function calculateNextDate(): string
    {
        $current = $this->next_execution_date ? $this->next_execution_date->copy() : now();
        return match ($this->recurrence) {
            'monthly' => $current->addMonth()->day(min($this->recurrence_day ?? 1, $current->daysInMonth))->toDateString(),
            'quarterly' => $current->addMonths(3)->day(min($this->recurrence_day ?? 1, $current->daysInMonth))->toDateString(),
            'yearly' => $current->addYear()->day(min($this->recurrence_day ?? 1, $current->daysInMonth))->toDateString(),
            default => $current->addMonth()->toDateString(),
        };
    }
}
