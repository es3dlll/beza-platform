<?php

declare(strict_types=1);

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class KycProfile extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const TIER_1 = 1;

    public const TIER_2 = 2;

    public const TIER_3 = 3;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVISION = 'revision';

    protected $fillable = [
        'user_id',
        'tier',
        'id_front_path',
        'id_back_path',
        'selfie_path',
        'proof_of_address_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'tier' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeTier(Builder $query, int $tier): Builder
    {
        return $query->where('tier', $tier);
    }

    public function submit(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft KYC can be submitted');
        }
        if (!$this->id_front_path || !$this->id_back_path) {
            throw new \RuntimeException('ID documents are required before submission');
        }
        $this->status = self::STATUS_SUBMITTED;
        $this->save();
    }

    public function markUnderReview(int $reviewerId): void
    {
        if ($this->status !== self::STATUS_SUBMITTED) {
            throw new \RuntimeException('Only submitted KYC can be reviewed');
        }
        $this->status = self::STATUS_UNDER_REVIEW;
        $this->reviewed_by = $reviewerId;
        $this->save();
    }

    public function approve(int $reviewerId): void
    {
        if ($this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \RuntimeException('Only reviewed KYC can be approved');
        }
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->save();

        User::where('id', $this->user_id)->update(['kyc_tier' => $this->tier]);
    }

    public function reject(int $reviewerId, string $reason): void
    {
        if ($this->status !== self::STATUS_UNDER_REVIEW) {
            throw new \RuntimeException('Only reviewed KYC can be rejected');
        }
        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->save();
    }

    public function sendForRevision(int $reviewerId, string $reason): void
    {
        $this->status = self::STATUS_REVISION;
        $this->rejection_reason = $reason;
        $this->reviewed_by = $reviewerId;
        $this->save();
    }

    public function resubmit(): void
    {
        if ($this->status !== self::STATUS_REVISION) {
            throw new \RuntimeException('Only revision KYC can be resubmitted');
        }
        $this->status = self::STATUS_SUBMITTED;
        $this->rejection_reason = null;
        $this->reviewed_by = null;
        $this->reviewed_at = null;
        $this->save();
    }

    public function getAllowedTransitions(): array
    {
        return [
            self::STATUS_DRAFT => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_UNDER_REVIEW],
            self::STATUS_UNDER_REVIEW => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_REVISION],
            self::STATUS_REVISION => [self::STATUS_SUBMITTED],
            self::STATUS_APPROVED => [],
            self::STATUS_REJECTED => [],
        ];
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAllowedTransitions()[$this->status] ?? []);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
