<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class DeviceFingerprint extends Model
{
    protected $table = 'device_fingerprints';

    protected $fillable = [
        'wallet_id', 'fingerprint_hash', 'user_agent', 'ip_address',
        'device_type', 'app_version', 'os', 'screen_resolution',
        'trust_score', 'txn_count', 'is_trusted', 'last_seen_at',
    ];

    protected $casts = [
        'trust_score' => 'integer',
        'txn_count' => 'integer',
        'is_trusted' => 'boolean',
        'last_seen_at' => 'datetime',
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

    public function markSeen(): void
    {
        $this->increment('txn_count');
        $this->update(['last_seen_at' => now()]);
    }

    public function updateTrustScore(int $delta): void
    {
        $newScore = max(0, min(1000, $this->trust_score + $delta));
        $this->update([
            'trust_score' => $newScore,
            'is_trusted' => $newScore >= 700,
        ]);
    }
}
