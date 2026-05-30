<?php

declare(strict_types=1);

namespace Modules\Agent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AgentTransaction extends Model
{
    protected $table = 'agent_transactions';

    protected $fillable = [
        'id', 'agent_id', 'user_wallet_id', 'type', 'amount',
        'fee', 'commission', 'currency', 'cfe_transaction_id',
        'status', 'reference_id', 'idempotency_key', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee' => 'integer',
        'commission' => 'integer',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
