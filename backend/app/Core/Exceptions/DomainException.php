<?php

namespace App\Core\Exceptions;

class DomainException extends \RuntimeException
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
