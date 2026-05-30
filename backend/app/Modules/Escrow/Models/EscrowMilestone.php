<?php

declare(strict_types=1);

namespace Modules\Escrow\Models;

use Illuminate\Database\Eloquent\Model;

final class EscrowMilestone extends Model
{
    protected $table = 'escrow_milestones';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','escrow_id','milestone_number','description','amount','status','released_at'];
    protected function casts(): array { return ['milestone_number'=>'integer','amount'=>'integer','released_at'=>'datetime']; }

    public function escrow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EscrowAgreement::class, 'escrow_id');
    }
}
