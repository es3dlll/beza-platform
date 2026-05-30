<?php

declare(strict_types=1);

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'token_hash',
        'refresh_token_hash',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function extend(int $minutes = 1440): void
    {
        $this->expires_at = now()->addMinutes($minutes);
        $this->last_activity = now();
        $this->save();
    }

    public function updateActivity(): void
    {
        $this->last_activity = now();
        $this->save();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public function invalidate(): void
    {
        $this->expires_at = now()->subMinute();
        $this->save();
    }
}
