<?php

declare(strict_types=1);

namespace Modules\Remittance\Exceptions;

use Exception;

final class RemittanceReceivingCountryBlockedException extends Exception
{
    public function __construct(string $country)
    {
        parent::__construct("Receiving country blocked for remittances: {$country}");
    }
}
