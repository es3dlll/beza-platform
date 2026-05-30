<?php

declare(strict_types=1);

namespace Modules\Bills\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Identity\Models\User;

class BillPayment extends Model
{
    use HasUlids;

    protected $table = 'bill_payments';

    protected $fillable = [
        'user_id', 'bill_provider_id', 'account_number', 'account_name',
        'biller_reference', 'period', 'due_date', 'amount_due', 'amount_paid',
        'fee_amount', 'total_debited', 'status', 'failure_reason',
        'refund_reason', 'retry_count', 'last_retry_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'last_retry_at' => 'datetime',
            'amount_due' => 'integer',
            'amount_paid' => 'integer',
            'fee_amount' => 'integer',
            'total_debited' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(BillProvider::class, 'bill_provider_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
