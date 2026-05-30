<?php

declare(strict_types=1);

namespace Modules\Investments\Exceptions;

use Exception;

final class FundNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Investment fund not found: {$id}");
    }
}

final class SubscriptionNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Subscription not found: {$id}");
    }
}

final class MinimumInvestmentException extends Exception
{
    public function __construct(int $minimum, int $provided)
    {
        parent::__construct("Minimum investment is {$minimum}, provided {$provided}");
    }
}
