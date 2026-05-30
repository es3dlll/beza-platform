<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Models;

use Illuminate\Database\Eloquent\Model;

final class HumanitarianDisbursement extends Model
{
    protected $table = 'humanitarian_disbursements';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','program_id','user_id','beneficiary_id','amount','currency','type','status','reference_number','failure_reason','disbursed_at','pickup_code','voucher_code','ofac_flagged','ofac_checked_at','disbursement_batch_id','metadata'];
    protected function casts(): array { return ['amount'=>'integer','disbursed_at'=>'datetime','ofac_flagged'=>'boolean','ofac_checked_at'=>'datetime','metadata'=>'array']; }
    public function program(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(HumanitarianProgram::class, 'program_id'); }
}
