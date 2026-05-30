<?php

declare(strict_types=1);

namespace Modules\Agent\Exceptions;

use Exception;

final class AgentLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $limitType,
        public readonly int $limit,
        public readonly int $current,
        public readonly int $requested,
    ) {
        parent::__construct("Agent $limitType limit exceeded: $current used, $limit max, $requested requested");
    }
}
