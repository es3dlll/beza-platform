<?php

namespace App\Modules\Team\Models;

use App\Models\User;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return TeamFactory::new();
    }

    protected $fillable = [
        'name',
        'owner_id',
        'description',
        'max_depth',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'max_depth' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function delegationLogs(): HasMany
    {
        return $this->hasMany(DelegationLog::class);
    }
}
