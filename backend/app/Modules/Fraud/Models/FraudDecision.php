<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FraudDecision extends Model
{
    protected $table = 'fraud_decisions';

    protected $fillable = [
        'wallet_id', 'rule_id', 'device_fingerprint_id', 'action',
        'score_before', 'score_after', 'score_impact', 'reason', 'reason_ar',
        'context_type', 'context_id', 'reference_id',
        'resolved_by', 'resolution', 'resolved_at',
    ];

    protected $casts = [
        'score_before' => 'integer',
        'score_after' => 'integer',
        'score_impact' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    public function rule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FraudRule::class, 'rule_id');
    }

    public function device(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeviceFingerprint::class, 'device_fingerprint_id');
    }

    public function isBlocking(): bool
    {
        return in_array($this->action, ['block', 'hold'], true);
    }
}
