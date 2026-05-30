<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CfeTransactionLine extends Model
{
    protected $table = 'cfe_transaction_lines';

    protected $fillable = [
        'id',
        'cfe_transaction_id',
        'account_id',
        'amount',
        'type',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CfeTransaction::class, 'cfe_transaction_id');
    }
}
