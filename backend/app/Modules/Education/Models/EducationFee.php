<?php

declare(strict_types=1);

namespace Modules\Education\Models;

use Illuminate\Database\Eloquent\Model;

final class EducationFee extends Model
{
    protected $table = 'education_fees';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','student_id','fee_type','amount','paid_amount','currency','due_date','status','receipt_number','paid_at','payment_method','cfe_transaction_id','reminded_at','metadata'];
    protected function casts(): array { return ['amount'=>'integer','paid_amount'=>'integer','due_date'=>'date','paid_at'=>'datetime','reminded_at'=>'datetime','metadata'=>'json']; }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(EducationStudent::class, 'student_id'); }
}
