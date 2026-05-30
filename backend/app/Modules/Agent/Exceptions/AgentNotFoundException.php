<?php

declare(strict_types=1);

namespace Modules\Agent\Exceptions;

use Exception;

final class AgentNotFoundException extends Exception
{
    public function __construct(string $agentId)
    {
        parent::__construct("Agent not found: $agentId");
    }
}
