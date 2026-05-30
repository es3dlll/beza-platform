<?php

declare(strict_types=1);

namespace Modules\Agent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AgentCommission extends Model
{
    protected $table = 'agent_commissions';

    protected $fillable = [
        'id', 'agent_id', 'agent_transaction_id', 'amount',
        'type', 'currency', 'status', 'settled_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'settled_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
