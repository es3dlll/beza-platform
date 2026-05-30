<?php

declare(strict_types=1);

namespace Modules\Investments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class InvestmentNav extends Model
{
    protected $table = 'investment_navs';

    protected $fillable = [
        'id', 'fund_id', 'nav', 'recorded_at',
    ];

    protected $casts = [
        'nav' => 'integer',
        'recorded_at' => 'date',
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

    public function fund(): BelongsTo
    {
        return $this->belongsTo(InvestmentFund::class, 'fund_id');
    }
}
