<?php

namespace App\Core\Exceptions;

class InvalidCurrencyException extends DomainException
{
    public function __construct(string $message = 'عملة غير متطابقة بين المحافظ.')
    {
        parent::__construct($message, 422);
    }
}
