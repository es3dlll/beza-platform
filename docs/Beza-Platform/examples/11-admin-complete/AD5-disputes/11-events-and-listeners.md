# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DisputeOpened / DisputeResolved

```php
<?php
// app/Events/Admin/DisputeOpened.php
class DisputeOpened
{
    use Dispatchable;

    public function __construct(
        public readonly Dispute $dispute,
    ) {}
}

// app/Events/Admin/DisputeResolved.php
class DisputeResolved
{
    use Dispatchable;

    public function __construct(
        public readonly Dispute $dispute,
    ) {}
}
```

## Listener: SendDisputeNotification

```php
<?php
// app/Listeners/Admin/SendDisputeNotification.php

namespace App\Listeners\Admin;

use App\Events\Admin\DisputeOpened;
use App\Events\Admin\DisputeResolved;
use App\Notifications\DisputeNotification;
use App\Notifications\Admin\NewDisputeAlert;
use App\Models\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Notification;

class SendDisputeNotification
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(DisputeOpened::class, [$this, 'handleOpened']);
        $events->listen(DisputeResolved::class, [$this, 'handleResolved']);
    }

    public function handleOpened(DisputeOpened $event): void
    {
        // إشعار للمشرفين
        $admins = User::where('is_admin', true)->get();
        Notification::send($admins, new NewDisputeAlert($event->dispute));

        // إشعار لمقدم النزاع
        $event->dispute->complainant->notify(new DisputeNotification(
            type: 'opened',
            dispute: $event->dispute,
        ));
    }

    public function handleResolved(DisputeResolved $event): void
    {
        // إشعار للطرفين
        $event->dispute->complainant->notify(new DisputeNotification(
            type: 'resolved',
            dispute: $event->dispute,
        ));

        $event->dispute->respondent->notify(new DisputeNotification(
            type: 'resolved',
            dispute: $event->dispute,
        ));
    }
}
```
