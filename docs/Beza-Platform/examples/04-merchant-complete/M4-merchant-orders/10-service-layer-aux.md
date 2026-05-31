# 10 - OrderStatusTransitionService

```php
<?php
namespace App\Services\Merchant;

class OrderStatusTransitionService
{
    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function getAllowedTransitions(string $currentStatus): array {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    public function canTransition(string $from, string $to): bool {
        return in_array($to, self::TRANSITIONS[$from] ?? []);
    }
}
```
