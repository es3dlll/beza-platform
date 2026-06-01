<?php

namespace App\Modules\Team\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelegationLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'team_id',
        'granter_id',
        'grantee_id',
        'permissions',
        'action',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'metadata' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granter_id');
    }

    public function grantee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grantee_id');
    }
}
