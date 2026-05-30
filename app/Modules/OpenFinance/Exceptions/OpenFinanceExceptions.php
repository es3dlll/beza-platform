<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

class AppNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("App not found: {$id}"); }
}
class ConsentNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Consent not found: {$id}"); }
}
class ConsentExpiredException extends Exception
{
    public function __construct() { parent::__construct('Consent has expired'); }
}
class InvalidScopeException extends Exception
{
    public function __construct(string $scope) { parent::__construct("Invalid scope: {$scope}"); }
}
