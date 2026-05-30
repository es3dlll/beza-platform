<?php

declare(strict_types=1);

namespace Modules\Identity\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $userId,
        public string $phone,
        public Carbon $timestamp,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('identity'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'phone' => $this->phone,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
