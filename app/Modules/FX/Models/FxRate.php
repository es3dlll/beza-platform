<?php

declare(strict_types=1);

namespace Modules\FX\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FxRate extends Model
{
    protected $table = 'fx_rates';

    protected $fillable = [
        'id', 'base_currency', 'quote_currency',
        'bid_rate', 'mid_rate', 'ask_rate', 'spread_pct',
        'rate_type', 'source', 'valid_from', 'valid_to', 'published_at',
    ];

    protected $casts = [
        'bid_rate' => 'float',
        'mid_rate' => 'float',
        'ask_rate' => 'float',
        'spread_pct' => 'float',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'published_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function quotes(): HasMany
    {
        return $this->hasMany(FxQuote::class, 'fx_rate_id');
    }

    public function isActive(): bool
    {
        $now = now();
        return $this->valid_from <= $now && (!$this->valid_to || $this->valid_to > $now);
    }

    public function pair(): string
    {
        return "{$this->base_currency}/{$this->quote_currency}";
    }
}
