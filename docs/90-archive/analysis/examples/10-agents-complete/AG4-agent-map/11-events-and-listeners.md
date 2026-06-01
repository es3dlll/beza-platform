# 11 - الأحداث والمستمعين (Events & Listeners) — الخريطة

## Event: AgentLocationUpdated

```php
<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Agent $agent,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("agent.{$this->agent->id}")];
    }
}
```

## Listener: BroadcastLocationUpdate

```php
<?php

namespace App\Listeners;

use App\Events\AgentLocationUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BroadcastLocationUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 2;

    public function handle(AgentLocationUpdated $event): void
    {
        // Broadcasted via ShouldBroadcast — listener acknowledges
        Log::info('Location broadcasted', [
            'agent_id' => $event->agent->id,
            'lat' => $event->latitude,
            'lng' => $event->longitude,
        ]);
    }

    public function failed(AgentLocationUpdated $event, \Throwable $e): void
    {
        Log::warning('Location broadcast failed', [
            'error' => $e->getMessage(),
        ]);
    }
}
```

## التسجيل (Registration)

```php
protected $listen = [
    AgentLocationUpdated::class => [
        BroadcastLocationUpdate::class,
    ],
];
```

## Why Broadcast?
- Real-time location sharing between agents
- Private channel ensures data isolation
- Pusher/reverb handles WebSocket delivery
