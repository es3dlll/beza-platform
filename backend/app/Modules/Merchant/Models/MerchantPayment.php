<?php

declare(strict_types=1);

namespace Modules\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Identity\Models\User;

class MerchantPayment extends Model
{
    use HasUlids;

    protected $table = 'merchant_payments';

    protected $fillable = [
        'merchant_id', 'store_id', 'payer_user_id', 'qr_code', 'qr_type',
        'amount', 'mdr_fee', 'net_amount', 'currency', 'status',
        'failure_reason', 'refund_reason', 'paid_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'mdr_fee' => 'integer',
            'net_amount' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function store()
    {
        return $this->belongsTo(MerchantStore::class);
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }
}
