<?php

declare(strict_types=1);

namespace Modules\Remittance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Identity\Models\User;

final class RemittanceOrder extends Model
{
    use HasUlids;

    protected $table = 'remittance_orders';

    protected $fillable = [
        'id',
        'corridor_id',
        'sender_user_id',
        'sender_country',
        'sender_full_name',
        'sender_phone',
        'sender_id_document',
        'beneficiary_id',
        'source_amount',
        'source_currency',
        'target_amount',
        'target_currency',
        'fx_rate_applied',
        'fx_quote_id',
        'fee_amount_in_source',
        'fee_amount_in_target',
        'total_cost',
        'payout_method',
        'payout_wallet_id',
        'payout_agent_id',
        'payout_bank_account',
        'purpose_code',
        'source_of_funds_declaration',
        'status',
        'compliance_result',
        'compliance_case_id',
        'reference_number',
        'failure_reason',
        'refund_reason',
        'paid_in_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_amount' => 'integer',
            'target_amount' => 'integer',
            'fx_rate_applied' => 'decimal:6',
            'fee_amount_in_source' => 'integer',
            'fee_amount_in_target' => 'integer',
            'total_cost' => 'integer',
            'paid_in_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function corridor()
    {
        return $this->belongsTo(Corridor::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
