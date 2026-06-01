<?php

namespace App\Core\Exceptions;

class InsufficientFundsException extends DomainException
{
    public function __construct(string $message = 'الرصيد غير كافٍ لإتمام العملية.')
    {
        parent::__construct($message, 402);
    }
}
