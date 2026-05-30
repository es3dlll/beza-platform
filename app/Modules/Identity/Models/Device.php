<?php

declare(strict_types=1);

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'fcm_token',
        'ip_address',
        'is_trusted',
        'last_used_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function sessions()
    {
        return $this->hasMany(Session::class, 'device_id', 'id');
    }

    public function markAsTrusted(): void
    {
        $this->is_trusted = true;
        $this->save();
    }

    public function markAsUntrusted(): void
    {
        $this->is_trusted = false;
        $this->save();
    }

    public function updateLastUsed(): void
    {
        $this->last_used_at = now();
        $this->save();
    }
}
