<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $table = 'loan_repayments';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','loan_id','installment_number','amount','paid_amount','due_date','status','paid_at'];
    protected function casts(): array { return ['amount'=>'integer','paid_amount'=>'integer','due_date'=>'date','paid_at'=>'datetime']; }
}
