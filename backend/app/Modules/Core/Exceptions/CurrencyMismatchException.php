<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

use App\Modules\Core\Enums\Currency;
use InvalidArgumentException;

final class CurrencyMismatchException extends InvalidArgumentException
{
    public function __construct(Currency $expected, Currency $given)
    {
        parent::__construct(
            sprintf('عملة غير متطابقة: متوقع %s، مستلم %s', $expected->value, $given->value)
        );
    }
}
