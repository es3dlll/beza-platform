<?php

declare(strict_types=1);

namespace Modules\Takaful\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TakafulClaim extends Model
{
    protected $table = 'takaful_claims';

    protected $fillable = [
        'id', 'policy_id', 'claim_number', 'amount', 'reason', 'status',
        'approved_amount', 'cfe_payout_tx_id', 'filed_at',
        'approved_at', 'rejected_at', 'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'approved_amount' => 'integer',
        'filed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $claim) {
            if (empty($claim->id)) {
                $claim->id = (string) Str::ulid();
            }
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(TakafulPolicy::class, 'policy_id');
    }
}
