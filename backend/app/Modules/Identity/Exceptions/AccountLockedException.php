<?php

declare(strict_types=1);

namespace Modules\Identity\Exceptions;

final class AccountLockedException extends \RuntimeException
{
    public function __construct(string $phone)
    {
        parent::__construct("Account {$phone} is temporarily locked due to too many attempts. Try again later.");
    }
}
