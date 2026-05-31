<?php

declare(strict_types=1);

namespace App\Modules\Fx\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class ExchangeRate extends Model
{
    protected $fillable = [
        'rate_source_id', 'base_currency', 'quote_currency',
        'buy_rate', 'sell_rate', 'spread_bps',
        'valid_from', 'valid_until', 'status',
    ];
    protected $casts = [
        'buy_rate' => 'integer',
        'sell_rate' => 'integer',
        'spread_bps' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
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

    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RateSource::class, 'rate_source_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->valid_from <= now()
            && ($this->valid_until === null || $this->valid_until > now());
    }

    public function toBuyCents(int $amountInMinor): int
    {
        return (int) floor(($amountInMinor * 10000) / $this->buy_rate);
    }

    public function toSellCents(int $amountInMinor): int
    {
        return (int) floor(($amountInMinor * 10000) / $this->sell_rate);
    }

    public function convert(int $amount, bool $isBuyingQuote): int
    {
        $rate = $isBuyingQuote ? $this->buy_rate : $this->sell_rate;
        $adjusted = $amount * 10000;
        $result = (int) floor($adjusted / $rate);
        return $result;
    }
}
