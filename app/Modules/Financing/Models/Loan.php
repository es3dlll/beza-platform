<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = 'loans';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','loan_product_id','principal','total_repayable','outstanding_balance','interest_rate','term_days','status','purpose','approved_at','disbursed_at','completed_at'];
    protected function casts(): array { return ['principal'=>'integer','total_repayable'=>'integer','outstanding_balance'=>'integer','interest_rate'=>'float','term_days'=>'integer','approved_at'=>'datetime','disbursed_at'=>'datetime','completed_at'=>'datetime']; }
}
