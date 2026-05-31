# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: MerchantApproved

```php
<?php
// app/Events/Admin/MerchantApproved.php
class MerchantApproved
{
    use Dispatchable;

    public function __construct(
        public readonly Merchant $merchant,
    ) {}
}

// app/Events/Admin/MerchantRejected.php
class MerchantRejected
{
    use Dispatchable;

    public function __construct(
        public readonly Merchant $merchant,
        public readonly string   $reason,
    ) {}
}

// app/Events/Admin/AgentApproved.php
class AgentApproved
{
    use Dispatchable;

    public function __construct(
        public readonly $agent,
    ) {}
}

// app/Events/Admin/AgentRejected.php
class AgentRejected
{
    use Dispatchable;

    public function __construct(
        public readonly $agent,
        public readonly string $reason,
    ) {}
}
```

## Listener: SendApprovalNotification

```php
<?php
// app/Listeners/Admin/SendApprovalNotification.php

namespace App\Listeners\Admin;

use App\Events\Admin\AgentApproved;
use App\Events\Admin\AgentRejected;
use App\Events\Admin\MerchantApproved;
use App\Events\Admin\MerchantRejected;
use App\Services\Admin\ApprovalNotificationService;
use Illuminate\Events\Dispatcher;

class SendApprovalNotification
{
    public function __construct(
        private readonly ApprovalNotificationService $notificationService
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MerchantApproved::class, [$this, 'handleMerchantApproved']);
        $events->listen(MerchantRejected::class, [$this, 'handleMerchantRejected']);
        $events->listen(AgentApproved::class, [$this, 'handleAgentApproved']);
        $events->listen(AgentRejected::class, [$this, 'handleAgentRejected']);
    }

    public function handleMerchantApproved(MerchantApproved $event): void
    {
        $this->notificationService->notifyMerchantApproved($event->merchant);
    }

    public function handleMerchantRejected(MerchantRejected $event): void
    {
        $this->notificationService->notifyMerchantRejected(
            $event->merchant, $event->reason
        );
    }

    public function handleAgentApproved(AgentApproved $event): void
    {
        $this->notificationService->notifyAgentApproved($event->agent);
    }

    public function handleAgentRejected(AgentRejected $event): void
    {
        $this->notificationService->notifyAgentRejected(
            $event->agent, $event->reason
        );
    }
}
```

## EventServiceProvider

```php
protected $subscribe = [
    SendApprovalNotification::class,
];
```
