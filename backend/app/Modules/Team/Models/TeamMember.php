<?php

namespace App\Modules\Team\Models;

use App\Models\User;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return TeamMemberFactory::new();
    }

    protected $fillable = [
        'team_id',
        'user_id',
        'parent_id',
        'role',
        'level',
        'commission_rate',
        'daily_deposit_limit',
        'daily_withdrawal_limit',
        'status',
        'activated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'commission_rate' => 'decimal:2',
            'daily_deposit_limit' => 'integer',
            'daily_withdrawal_limit' => 'integer',
            'activated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
