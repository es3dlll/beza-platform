<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FraudRule extends Model
{
    protected $table = 'fraud_rules';

    protected $fillable = [
        'name', 'name_ar', 'type', 'category', 'action', 'scope', 'metric',
        'threshold', 'score_impact', 'kyc_tier_min', 'priority',
        'time_window_minutes', 'is_active',
    ];

    protected $casts = [
        'threshold' => 'integer',
        'score_impact' => 'integer',
        'priority' => 'integer',
        'time_window_minutes' => 'integer',
        'is_active' => 'boolean',
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

    public function isPreCheck(): bool
    {
        return $this->category === 'pre_check';
    }

    public function isPostMonitor(): bool
    {
        return $this->category === 'post_monitor';
    }

    public function decisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FraudDecision::class, 'rule_id');
    }
}
