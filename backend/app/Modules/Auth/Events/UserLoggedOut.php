<?php

declare(strict_types=1);

namespace Modules\Auth\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

final class UserLoggedOut implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public string $userId,
        public string $sessionId,
        public Carbon $timestamp,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('auth')];
    }

    public function broadcastAs(): string
    {
        return 'user.logged_out';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
