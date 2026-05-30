<?php

declare(strict_types=1);

namespace Modules\IAM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RoleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $roleId,
        public readonly string $name,
    ) {}
}
