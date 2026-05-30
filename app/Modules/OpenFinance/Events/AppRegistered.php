<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AppRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $appId,
        public readonly string $userId,
        public readonly string $appName,
    ) {}
}
