<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Agent extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'status',
        'verification_level',
        'available_balance_fils',
        'daily_liquidity_limit_fils',
        'region',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'available_balance_fils' => 'integer',
            'daily_liquidity_limit_fils' => 'integer',
            'rating' => 'float',
            'verification_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
