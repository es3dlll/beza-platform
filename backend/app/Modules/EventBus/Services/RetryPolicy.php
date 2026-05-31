<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

final class RetryPolicy
{
    private int $maxAttempts;
    private int $baseDelaySeconds;
    private int $multiplier;

    public function __construct()
    {
        $this->maxAttempts = (int) config('event_bus.retry.max_attempts', 3);
        $this->baseDelaySeconds = (int) config('event_bus.retry.base_delay_seconds', 60);
        $this->multiplier = (int) config('event_bus.retry.multiplier', 2);
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getDelaySeconds(int $attempt): int
    {
        if ($attempt < 1) {
            return 0;
        }

        $delay = $this->baseDelaySeconds * ($this->multiplier ** ($attempt - 1));

        return $delay;
    }

    public function canRetry(int $attempt): bool
    {
        return $attempt < $this->maxAttempts;
    }

    public function getAttemptsRemaining(int $attempt): int
    {
        return max(0, $this->maxAttempts - $attempt);
    }
}
