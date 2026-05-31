<?php

declare(strict_types=1);

namespace App\Modules\FX\Models;

use App\Modules\FX\Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ExchangeRate extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'exchange_rates';

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate_fils_per_unit',
        'bid_fils_per_unit',
        'ask_fils_per_unit',
        'provider',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'rate_fils_per_unit' => 'integer',
        'bid_fils_per_unit' => 'integer',
        'ask_fils_per_unit' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isValid(): bool
    {
        return $this->is_active
            && $this->valid_from <= now()
            && $this->valid_until > now();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>', now());
    }
}
