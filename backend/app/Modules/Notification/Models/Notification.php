<?php

declare(strict_types=1);

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Models\User;

final class Notification extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'type', 'channel', 'title', 'body', 'data',
        'action_url', 'icon', 'read_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
