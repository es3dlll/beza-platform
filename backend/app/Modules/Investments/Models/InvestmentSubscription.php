<?php

declare(strict_types=1);

namespace Modules\Investments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Models\User;
use Illuminate\Support\Str;

final class InvestmentSubscription extends Model
{
    protected $table = 'investment_subscriptions';

    protected $fillable = [
        'id', 'user_id', 'fund_id', 'type', 'units', 'unit_price',
        'total_amount', 'status', 'cfe_transaction_id', 'settled_at',
    ];

    protected $casts = [
        'units' => 'integer',
        'unit_price' => 'integer',
        'total_amount' => 'integer',
        'settled_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(InvestmentFund::class, 'fund_id');
    }
}
