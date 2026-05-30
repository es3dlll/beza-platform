<?php

declare(strict_types=1);

namespace Modules\Cards\Exceptions;

use Exception;

final class MerchantBlockedException extends Exception
{
    public function __construct(string $category)
    {
        parent::__construct("Merchant category blocked: {$category}");
    }
}
