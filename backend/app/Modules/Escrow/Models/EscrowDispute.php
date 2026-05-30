<?php

declare(strict_types=1);

namespace Modules\Escrow\Models;

use Illuminate\Database\Eloquent\Model;

class EscrowDispute extends Model
{
    protected $table = 'escrow_disputes';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','escrow_id','opened_by','reason','status','resolution','resolved_by','resolved_at'];
    protected function casts(): array { return ['resolved_at'=>'datetime']; }

    public function escrow(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EscrowAgreement::class, 'escrow_id');
    }

    public function opener(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'opened_by');
    }

    public function resolver(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Identity\Models\User::class, 'resolved_by');
    }
}
