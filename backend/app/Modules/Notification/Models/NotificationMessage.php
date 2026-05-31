<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NotificationMessage extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'notification_messages';

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'title',
        'body',
        'data',
        'status',
        'reference_type',
        'reference_id',
        'read_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function scopeUnread($q) { return $q->whereNull('read_at'); }
    public function scopeByType($q, string $type) { return $q->where('type', $type); }
    public function scopeByChannel($q, string $channel) { return $q->where('channel', $channel); }
    public function scopePending($q) { return $q->where('status', 'pending'); }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now(), 'status' => 'read']);
    }

    public function isRead(): bool { return $this->read_at !== null; }
}
