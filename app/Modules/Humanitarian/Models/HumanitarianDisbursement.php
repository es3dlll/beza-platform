<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Models;

use Illuminate\Database\Eloquent\Model;

class HumanitarianDisbursement extends Model
{
    protected $table = 'humanitarian_disbursements';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','program_id','user_id','beneficiary_id','amount','currency','type','status','reference_number','failure_reason','disbursed_at'];
    protected function casts(): array { return ['amount'=>'integer','disbursed_at'=>'datetime']; }
}
