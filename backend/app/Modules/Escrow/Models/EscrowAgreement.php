<?php

declare(strict_types=1);

namespace Modules\Escrow\Models;

use Illuminate\Database\Eloquent\Model;

final class EscrowAgreement extends Model
{
    protected $table = 'escrow_agreements';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','buyer_id','seller_id','reference_type','reference_id','total_amount','fee_amount','net_amount','currency','status','cfe_hold_id','description','expires_at','completed_at'];
    protected function casts(): array { return ['total_amount'=>'integer','fee_amount'=>'integer','net_amount'=>'integer','expires_at'=>'datetime','completed_at'=>'datetime']; }

    public function buyer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'buyer_id');
    }

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'seller_id');
    }

    public function milestones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EscrowMilestone::class, 'escrow_id');
    }

    public function disputes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EscrowDispute::class, 'escrow_id');
    }
}
