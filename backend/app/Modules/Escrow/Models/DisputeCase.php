<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Models;

use App\Modules\Escrow\Database\Factories\DisputeCaseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DisputeCase extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'escrow_dispute_cases';

    protected $fillable = [
        'escrow_transaction_id',
        'raised_by',
        'reason',
        'description',
        'documents',
        'status',
        'decision',
        'decision_reason',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'resolved_at' => 'datetime',
    ];

    const UPDATED_AT = null;

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(EscrowTransaction::class, 'escrow_transaction_id');
    }

    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeUnderReview($query) { return $query->where('status', 'under_review'); }

    public function isResolved(): bool { return $this->status === 'resolved'; }
}
