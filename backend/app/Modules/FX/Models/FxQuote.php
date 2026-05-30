<?php

declare(strict_types=1);

namespace Modules\FX\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FxQuote extends Model
{
    protected $table = 'fx_quotes';

    protected $fillable = [
        'id', 'requestor_id', 'requestor_type',
        'base_currency', 'quote_currency',
        'amount_in_base', 'amount_in_quote',
        'rate_used', 'rate_type', 'fx_rate_id',
        'status', 'ttl_seconds', 'expires_at',
        'accepted_at', 'expired_at',
    ];

    protected $casts = [
        'rate_used' => 'float',
        'ttl_seconds' => 'integer',
        'amount_in_base' => 'integer',
        'amount_in_quote' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function rate(): BelongsTo
    {
        return $this->belongsTo(FxRate::class, 'fx_rate_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(FxConversion::class, 'quote_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now() || $this->status === 'expired';
    }

    public function accept(): void
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
    }

    public function markExpired(): void
    {
        $this->status = 'expired';
        $this->expired_at = now();
    }
}
