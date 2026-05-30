<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CfeTransaction extends Model
{
    protected $table = 'cfe_transactions';

    protected $fillable = [
        'id',
        'reference_type',
        'reference_id',
        'description',
        'total_amount',
        'currency',
        'channel',
        'initiated_by',
        'status',
        'journal_entry_id',
        'failure_reason',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'completed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function lines(): HasMany
    {
        return $this->hasMany(CfeTransactionLine::class, 'cfe_transaction_id');
    }
}
