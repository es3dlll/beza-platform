<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

use InvalidArgumentException;

final class InvalidAmountException extends InvalidArgumentException
{
    public function __construct(string $message = 'المبلغ غير صالح')
    {
        parent::__construct($message);
    }
}
