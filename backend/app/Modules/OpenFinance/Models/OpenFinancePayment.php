<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

class OpenFinancePayment extends Model
{
    protected $table = 'open_finance_payments';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','consent_id','user_id','payment_type','recipient_id','amount','currency','description','idempotency_key','status','cfe_transaction_id','failure_reason','completed_at'];
    protected function casts(): array { return ['amount'=>'integer','completed_at'=>'datetime']; }

    public function consent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OpenFinanceConsent::class, 'consent_id');
    }
}
