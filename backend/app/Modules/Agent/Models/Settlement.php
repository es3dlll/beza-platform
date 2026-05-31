<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Modules\Agent\Database\Factories\SettlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'settlement_date', 'expected_amount', 'actual_amount',
        'difference', 'commission_amount', 'status', 'notes', 'resolved_by', 'settled_at',
    ];

    protected $casts = [
        'expected_amount' => 'integer',
        'actual_amount' => 'integer',
        'difference' => 'integer',
        'commission_amount' => 'integer',
        'settlement_date' => 'date',
        'settled_at' => 'datetime',
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

    protected static function newFactory(): SettlementFactory
    {
        return SettlementFactory::new();
    }

    public function agent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isBalanced(): bool
    {
        return $this->difference === 0;
    }
}
