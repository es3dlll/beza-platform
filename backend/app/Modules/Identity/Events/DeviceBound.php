<?php

declare(strict_types=1);

namespace Modules\Identity\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

final class DeviceBound implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public string $userId,
        public string $deviceId,
        public string $deviceName,
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
        return 'device.bound';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }
}
