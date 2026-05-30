<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

final class InvalidScopeException extends Exception
{
    public function __construct(string $scope) { parent::__construct("Invalid scope: {$scope}"); }
}
