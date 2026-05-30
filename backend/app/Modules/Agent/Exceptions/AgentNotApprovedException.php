<?php

declare(strict_types=1);

namespace Modules\Agent\Exceptions;

use Exception;

final class AgentNotApprovedException extends Exception
{
    public function __construct(string $agentId, string $status)
    {
        parent::__construct("Agent $agentId is not approved. Current status: $status");
    }
}
