<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Modules\Agent\Database\Factories\AgentTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AgentTransaction extends Model
{
    use HasFactory;

    protected $table = 'agent_transactions';

    protected $fillable = [
        'agent_id', 'type', 'status', 'customer_wallet_id', 'customer_phone',
        'customer_name', 'amount', 'currency', 'fee', 'commission_amount',
        'commission_rate_bps', 'settlement_date', 'idempotency_key',
        'transaction_id', 'description', 'description_ar', 'location_lat', 'location_lng',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'commission_amount' => 'integer',
        'commission_rate_bps' => 'integer',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'settlement_date' => 'date',
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

    protected static function newFactory(): AgentTransactionFactory
    {
        return AgentTransactionFactory::new();
    }

    public function agent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isCashIn(): bool
    {
        return $this->type === 'cash_in';
    }

    public function isCashOut(): bool
    {
        return $this->type === 'cash_out';
    }
}
