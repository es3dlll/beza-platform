<?php

declare(strict_types=1);

namespace Modules\Auth\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

final class PinCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public string $userId,
        public Carbon $timestamp,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('auth')];
    }

    public function broadcastAs(): string
    {
        return 'pin.created';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
