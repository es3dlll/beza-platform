<?php

declare(strict_types=1);

namespace Modules\GovCollections\Exceptions;

use Exception;

class GovServiceProviderNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Provider not found: {$id}"); }
}
