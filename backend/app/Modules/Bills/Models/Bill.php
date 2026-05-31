<?php

declare(strict_types=1);

namespace App\Modules\Bills\Models;

use App\Modules\Bills\Database\Factories\BillFactory;
use App\Modules\BillProvider\Models\BillProvider;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Bill extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'bills';

    protected $fillable = [
        'user_id',
        'bill_provider_id',
        'account_number',
        'amount_fils',
        'due_date',
        'status',
        'paid_at',
        'receipt_reference',
        'metadata',
    ];

    protected $casts = [
        'amount_fils' => 'integer',
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(BillProvider::class, 'bill_provider_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date?->isPast();
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, ['pending', 'overdue']);
    }
}
