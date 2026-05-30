<?php

declare(strict_types=1);

namespace Modules\Takaful\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class TakafulPolicy extends Model
{
    protected $table = 'takaful_policies';

    protected $fillable = [
        'id', 'user_id', 'product_id', 'policy_number', 'premium',
        'coverage_amount', 'start_date', 'end_date', 'status',
        'cfe_premium_tx_id', 'cfe_pool_account_id', 'metadata',
    ];

    protected $casts = [
        'premium' => 'integer',
        'coverage_amount' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'json',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $policy) {
            if (empty($policy->id)) {
                $policy->id = (string) Str::ulid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(TakafulProduct::class, 'product_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(TakafulClaim::class, 'policy_id');
    }
}
