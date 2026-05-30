<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Exceptions;

use Exception;

final class AppNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("App not found: {$id}"); }
}
final class ConsentNotFoundException extends Exception
{
    public function __construct(string $id) { parent::__construct("Consent not found: {$id}"); }
}
final class ConsentExpiredException extends Exception
{
    public function __construct() { parent::__construct('Consent has expired'); }
}
final class InvalidScopeException extends Exception
{
    public function __construct(string $scope) { parent::__construct("Invalid scope: {$scope}"); }
}
