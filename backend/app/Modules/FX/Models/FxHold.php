<?php

declare(strict_types=1);

namespace App\Modules\Fx\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FxHold extends Model
{
    protected $table = 'fx_holds';

    protected $fillable = [
        'wallet_id', 'base_currency', 'quote_currency', 'amount',
        'locked_rate', 'locked_spread_bps', 'converted_amount',
        'expires_at', 'status', 'idempotency_key',
    ];
    protected $casts = [
        'amount' => 'integer',
        'locked_rate' => 'integer',
        'locked_spread_bps' => 'integer',
        'converted_amount' => 'integer',
        'expires_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function consume(): void
    {
        $this->update(['status' => 'consumed']);
    }

    public function release(): void
    {
        $this->update(['status' => 'released']);
    }
}
