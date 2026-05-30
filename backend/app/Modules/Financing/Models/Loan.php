<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = 'loans';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','loan_product_id','product_type','principal','total_repayable','outstanding_balance','interest_rate','late_penalty_rate','term_days','credit_score','status','purpose','rejection_reason','approved_at','under_review_at','disbursed_at','completed_at','defaulted_at'];
    protected function casts(): array { return ['principal'=>'integer','total_repayable'=>'integer','outstanding_balance'=>'integer','interest_rate'=>'float','late_penalty_rate'=>'float','term_days'=>'integer','credit_score'=>'integer','approved_at'=>'datetime','under_review_at'=>'datetime','disbursed_at'=>'datetime','completed_at'=>'datetime','defaulted_at'=>'datetime']; }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'user_id');
    }

    public function repayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoanRepayment::class, 'loan_id');
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }
}
