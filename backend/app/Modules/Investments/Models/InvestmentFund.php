<?php

declare(strict_types=1);

namespace Modules\Investments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Identity\Models\User;
use Illuminate\Support\Str;

final class InvestmentFund extends Model
{
    protected $table = 'investment_funds';

    protected $fillable = [
        'id', 'name', 'name_ar', 'type', 'description', 'min_investment',
        'max_investment', 'current_nav', 'nav_updated_at', 'is_active', 'metadata',
    ];

    protected $casts = [
        'min_investment' => 'integer',
        'max_investment' => 'integer',
        'current_nav' => 'integer',
        'nav_updated_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'json',
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(InvestmentSubscription::class, 'fund_id');
    }

    public function navs(): HasMany
    {
        return $this->hasMany(InvestmentNav::class, 'fund_id');
    }

    public function latestNav(): ?InvestmentNav
    {
        return $this->navs()->latest('recorded_at')->first();
    }
}
