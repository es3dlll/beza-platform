<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $table = 'loan_repayments';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','loan_id','installment_number','amount','paid_amount','late_penalty','due_date','status','paid_at','reminded_at','payment_method'];
    protected function casts(): array { return ['amount'=>'integer','paid_amount'=>'integer','late_penalty'=>'integer','due_date'=>'date','paid_at'=>'datetime','reminded_at'=>'datetime']; }

    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
